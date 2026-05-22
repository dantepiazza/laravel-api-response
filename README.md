# dantepiazza/laravel-api-response

A fluent, expressive JSend-compliant JSON API response builder for Laravel 10+.

> Implements the [JSend specification](https://github.com/omniti-labs/jsend): every response carries a `status` of `success`, `fail`, or `error`, making frontend handling predictable and consistent by convention.

---

## Installation

```bash
composer require dantepiazza/laravel-api-response
```

The service provider and `ApiResponse` facade are auto-discovered via Laravel's package discovery. No additional setup required.

### Publish config (optional)

```bash
php artisan vendor:publish --tag=api-response-config
```

---

## Usage

Three ways to use the package — all share the same singleton instance and are fully interchangeable:

```php
// 1. Helper function
api()->success()->response();

// 2. Facade
use DantePiazza\LaravelApiResponse\Facades\ApiResponse;
ApiResponse::success()->response();

// 3. Dependency injection (recommended for testability)
use DantePiazza\LaravelApiResponse\ApiResponse;

class UserController extends Controller
{
    public function __construct(private ApiResponse $api) {}

    public function index(): JsonResponse
    {
        return $this->api->success($users, 'Users retrieved')->response();
    }
}
```

For a clean DI setup across all controllers, inject in the base `Controller`:

```php
// app/Http/Controllers/Controller.php
abstract class Controller
{
    public function __construct(protected ApiResponse $api) {}
}
```

---

## Response structure

The envelope follows the JSend standard. The `code` field mirrors the HTTP status code and is always present in the body for clients that don't inspect headers.

### `success` — 2xx

```json
{
    "status": "success",
    "code": 200,
    "message": "Users retrieved",
    "data": { ... }
}
```

`data` is always present. It will be `null` if nothing was provided.  
`message` is optional for `success` and `fail`.

### `fail` — 4xx

Client-side error. `data` describes what went wrong (e.g. validation errors).

```json
{
    "status": "fail",
    "code": 422,
    "message": "Validation failed",
    "data": {
        "email": "The email field is required.",
        "password": "Minimum 8 characters."
    }
}
```

### `error` — 5xx

Server-side error. `message` is **required** by JSend. `data` is optional.

```json
{
    "status": "error",
    "code": 500,
    "message": "Internal server error"
}
```

---

## API Reference

### 2xx — success

| Method | HTTP | Notes |
|--------|------|-------|
| `success(mixed $data = null, string $message)` | 200 | Standard success |
| `created(mixed $data = null, string $message)` | 201 | Resource created |
| `accepted(mixed $data = null, string $message)` | 202 | Async / queued |
| `noContent()` | 204 | No body |

### 4xx — fail

| Method | HTTP | Default message |
|--------|------|----------------|
| `badRequest(mixed $data = null, string $message)` | 400 | The request parameters are incorrect. |
| `unauthorized(mixed $data = null, string $message)` | 401 | Unauthorized |
| `forbidden(mixed $data = null, string $message)` | 403 | Forbidden |
| `notFound(mixed $data = null, string $message)` | 404 | Not found |
| `methodNotAllowed(mixed $data = null, string $message)` | 405 | Method not allowed |
| `conflict(mixed $data = null, string $message)` | 409 | Conflict |
| `validationError(mixed $data = null, string $message)` | 422 | Validation failed |
| `tooManyRequests(mixed $data = null, string $message)` | 429 | Too many requests |

### 5xx — error

| Method | HTTP | Default message |
|--------|------|----------------|
| `serverError(mixed $data = null, string $message)` | 500 | Internal server error |
| `serviceUnavailable(mixed $data = null, string $message)` | 503 | Service unavailable |

### Generic setter

Use when you need full control, e.g. inside a macro:

```php
api()->set(ApiResponse::STATUS_FAIL, 409, 'Already exists', $data);

// Status constants:
ApiResponse::STATUS_SUCCESS  // 'success'
ApiResponse::STATUS_FAIL     // 'fail'
ApiResponse::STATUS_ERROR    // 'error'
```

---

## Data helpers

### Merging extra data

```php
api()->success()
     ->data(['token' => $token, 'expires_in' => 3600])
     ->response();
```

Multiple `->data()` calls merge into the same array.

### Message-only override

```php
api()->success()->message('Custom message')->response();
```

### Validation errors

Pass errors directly as `$data` — they land in `data` per the JSend spec:

```php
api()->validationError('Validation failed', $validator->errors()->toArray())->response();
```

```json
{
    "status": "fail",
    "code": 422,
    "message": "Validation failed",
    "data": {
        "email": ["The email field is required."],
        "name":  ["The name must be at least 3 characters."]
    }
}
```

---

## Pagination

### Laravel paginator (automatic)

Pass a `LengthAwarePaginator` directly — all fields are extracted automatically:

```php
$users = User::paginate(15);

return api()->success()
            ->records($users)
            ->response();
```

### Manual array / Collection

```php
return api()->success()
            ->records($users, total: 120, page: 2, pageSize: 15)
            ->response();
```

If `total` is omitted it defaults to `count($items)`.

### Pagination output

Both cases produce the same structure:

```json
{
    "status": "success",
    "code": 200,
    "message": "Users retrieved",
    "data": {
        "results": [...],
        "pagination": {
            "total": 120,
            "per_page": 15,
            "current_page": 2,
            "last_page": 8,
            "from": 16,
            "to": 30,
            "links": {
                "prev": "https://example.com/api/users?page=1",
                "next": "https://example.com/api/users?page=3"
            }
        }
    }
}
```

---

## Cookies

```php
api()->success()
     ->withCookie(cookie('token', $token, 60))
     ->withoutCookie('old_session')
     ->response();
```

---

## Configuration

After publishing the config you can rename any top-level JSON key to match an existing API contract:

```php
// config/api-response.php
return [
    'keys' => [
        'status'  => 'status',   // default: 'status'
        'code'    => 'code',     // default: 'code'
        'message' => 'message',  // default: 'message'
        'data'    => 'data',     // default: 'data'
    ],
];
```

---

## Macros

Extend the class with custom shorthand methods in any service provider:

```php
use DantePiazza\LaravelApiResponse\ApiResponse;

ApiResponse::macro('teapot', function () {
    return $this->set(ApiResponse::STATUS_FAIL, 418, "I'm a teapot");
});

// Then anywhere:
api()->teapot()->response();
```

---

## Supported data types

`data()`, `records()`, and the `$data` argument of all shorthand methods accept:

- Plain `array`
- Any `object` (cast via `(array)`)
- `Illuminate\Support\Collection`
- `Illuminate\Http\Resources\Json\JsonResource`
- `Illuminate\Contracts\Pagination\LengthAwarePaginator` (in `records()` only)

---

## Frontend — TypeScript model

The package ships `ApiResponse.ts`, a single TypeScript class that maps the JSend envelope into a typed object.

```ts
import { ApiResponse } from './ApiResponse';

const res = ApiResponse.from(await http.get('/api/users'));

if (res.isSuccess()) {
    console.log(res.data);      // unknown | null
    console.log(res.message);   // string

    // Paginated response
    const paged = res.records<User>();
    if (paged) {
        paged.records       // User[]
        paged.total         // number
        paged.currentPage   // number
        paged.hasNextPage   // boolean
    }
}

if (res.isFail()) {
    // data contains the failure detail (e.g. validation errors)
    console.log(res.message, res.data);
}

if (res.isError()) {
    console.error(res.message);
}
```

Safe factory (returns `null` instead of throwing on invalid input):

```ts
const res = ApiResponse.tryFrom(raw); // ApiResponse | null
```

---

## Testing

```bash
composer install
vendor/bin/phpunit
```

---

## License

MIT
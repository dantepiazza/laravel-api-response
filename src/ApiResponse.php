<?php

namespace DantePiazza\LaravelApiResponse;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

/**
 * JSend-compliant API response builder.
 *
 * Status values:
 *   - "success" : 2xx, operation completed, data always present (may be null).
 *   - "fail"    : 4xx, client-side problem, data holds the failure detail.
 *   - "error"   : 5xx, server-side problem, message is required, data is optional.
 *
 * @link https://github.com/omniti-labs/jsend
 */
class ApiResponse
{
    use Macroable;

    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAIL    = 'fail';
    public const STATUS_ERROR   = 'error';

    protected string  $status  = self::STATUS_SUCCESS;
    protected int     $code    = 200;
    protected ?string $message = null;
    protected ?array  $data    = null;
    protected array   $cookies = [];
    protected array   $headers = [];
    protected array   $cookiesToRemove = [];

    // -------------------------------------------------------------------------
    // Core setter
    // -------------------------------------------------------------------------

    public function set(string $status, int $code, ?string $message = null, mixed $data = null): static
    {
        $this->status  = $status;
        $this->code    = $code;
        $this->message = $message;
        $this->data    = $data !== null
            ? array_merge($this->data ?? [], $this->transformData($data))
            : $this->data;

        return $this;
    }

    public function message(string $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function data(array|object $data): static
    {
        $this->data = array_merge($this->data ?? [], $this->transformData($data));
        return $this;
    }

    /** Append a structured error entry. */
    public function error(string $error, string $description, ?string $code = null): static
    {
        $this->data[] = array_filter([
            'error'       => $error,
            'description' => $description,
            'code'        => $code,
        ]);

        return $this;
    }

    // -------------------------------------------------------------------------
    // Pagination
    // -------------------------------------------------------------------------

    /**
     * Attach a paginated result set.
     *
     * CASE 1 — Laravel LengthAwarePaginator (automatic):
     *   ->records($paginator)
     *
     * CASE 2 — Manual array / Collection:
     *   ->records($items, total: 120, page: 2, pageSize: 15)
     *   $total defaults to count($items) if omitted.
     */
    public function records(mixed $resource, ?int $total = null, int $page = 1, int $pageSize = 10): static
    {
        $this->data ??= [];

        if ($resource instanceof LengthAwarePaginator) {
            $items = $resource->getCollection();
            
            $this->data['results']    = $this->transformData($items);
            $this->data['pagination'] = [
                'total'        => $resource->total(),
                'per_page'     => $resource->perPage(),
                'current_page' => $resource->currentPage(),
                'last_page'    => $resource->lastPage(),
                'from'         => $resource->firstItem(),
                'to'           => $resource->lastItem(),
                'links'        => [
                    'prev' => $resource->previousPageUrl(),
                    'next' => $resource->nextPageUrl(),
                ],
            ];
            return $this;
        }

        $results    = $this->transformData($resource);
        $totalCount = $total ?? count($results);
        $page       = max(1, $page);
        $pageSize   = max(1, $pageSize);
        $lastPage   = $totalCount > 0 ? (int) ceil($totalCount / $pageSize) : 1;
        $from       = $totalCount > 0 ? ($page - 1) * $pageSize + 1 : null;
        $to         = $totalCount > 0 ? min($page * $pageSize, $totalCount) : null;

        $this->data['results']    = $results;
        $this->data['pagination'] = [
            'total'        => $totalCount,
            'per_page'     => $pageSize,
            'current_page' => $page,
            'last_page'    => $lastPage,
            'from'         => $from,
            'to'           => $to,
            'links'        => [
                'prev' => null,
                'next' => null,
            ],
        ];

        return $this;
    }

    // -------------------------------------------------------------------------
    // 2xx — success
    // -------------------------------------------------------------------------

    public function success(mixed $data = null, string $message = ''): static
    {
        $this->status  = self::STATUS_SUCCESS;
        $this->code    = 200;
        $this->message = $message ?: null;
        $this->data    = $data !== null ? $this->transformData($data) : null;
        return $this;
    }

    public function created(mixed $data = null, string $message = ''): static
    {
        $this->status  = self::STATUS_SUCCESS;
        $this->code    = 201;
        $this->message = $message ?: null;
        $this->data    = $data !== null ? $this->transformData($data) : null;
        return $this;
    }

    public function accepted(mixed $data = null, string $message = ''): static
    {
        $this->status  = self::STATUS_SUCCESS;
        $this->code    = 202;
        $this->message = $message ?: null;
        $this->data    = $data !== null ? $this->transformData($data) : null;
        return $this;
    }

    public function noContent(): static
    {
        $this->status  = self::STATUS_SUCCESS;
        $this->code    = 204;
        $this->message = null;
        $this->data    = null;
        return $this;
    }

    // -------------------------------------------------------------------------
    // 4xx — fail
    // -------------------------------------------------------------------------

    public function badRequest(mixed $data = null, string $message = 'The request parameters are incorrect.'): static
    {
        return $this->setFail(400, $message, $data);
    }

    public function unauthorized(mixed $data = null, string $message = 'Unauthorized'): static
    {
        return $this->setFail(401, $message, $data);
    }

    public function forbidden(mixed $data = null, string $message = 'Forbidden'): static
    {
        return $this->setFail(403, $message, $data);
    }

    public function notFound(mixed $data = null, string $message = 'Not found'): static
    {
        return $this->setFail(404, $message, $data);
    }

    public function methodNotAllowed(mixed $data = null, string $message = 'Method not allowed'): static
    {
        return $this->setFail(405, $message, $data);
    }

    public function conflict(mixed $data = null, string $message = 'Conflict'): static
    {
        return $this->setFail(409, $message, $data);
    }

    public function validationError(mixed $data = null, string $message = 'Validation failed'): static
    {
        return $this->setFail(422, $message, $data);
    }

    public function tooManyRequests(mixed $data = null, string $message = 'Too many requests'): static
    {
        return $this->setFail(429, $message, $data);
    }

    // -------------------------------------------------------------------------
    // 5xx — error
    // -------------------------------------------------------------------------

    public function serverError(mixed $data = null, string $message = 'Internal server error'): static
    {
        return $this->setError(500, $message, $data);
    }

    public function serviceUnavailable(mixed $data = null, string $message = 'Service unavailable'): static
    {
        return $this->setError(503, $message, $data);
    }

    // -------------------------------------------------------------------------
    // Cookie helpers
    // -------------------------------------------------------------------------

    public function withCookie(mixed $cookie): static
    {
        $this->cookies[] = $cookie;
        return $this;
    }

    public function withoutCookie(string $name): static
    {
        $this->cookiesToRemove[] = $name;
        return $this;
    }

    // -------------------------------------------------------------------------
    // Headers helpers
    // -------------------------------------------------------------------------

    public function withHeader(string $name, string $value): static
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function withHeaders(array $headers): static
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    // -------------------------------------------------------------------------
    // Build & send
    // -------------------------------------------------------------------------

    public function response(): JsonResponse
    {
        $response = response()->json($this->buildPayload(), $this->code);

        foreach ($this->headers as $name => $value) {
            $response->header($name, $value);
        }
        foreach ($this->cookies as $cookie) {
            $response->withCookie($cookie);
        }
        foreach ($this->cookiesToRemove as $name) {
            $response->withoutCookie($name);
        }

        $this->reset();

        return $response;
    }

    /**
     * Build the payload array without sending.
     *
     * JSend rules:
     *   success/fail : { status, code, ?message, data }   — data always present (null ok)
     *   error        : { status, code, message, ?data }   — message required, data optional
     */
    public function buildPayload(): array
    {
        $statusKey  = config('api-response.keys.status',  'status');
        $codeKey    = config('api-response.keys.code',    'code');
        $messageKey = config('api-response.keys.message', 'message');
        $dataKey    = config('api-response.keys.data',    'data');

        $payload = [
            $statusKey => $this->status,
            $codeKey   => $this->code,
        ];

        if ($this->status === self::STATUS_ERROR) {
            $payload[$messageKey] = $this->message ?? 'An unexpected error occurred.';
            if ($this->data !== null) {
                $payload[$dataKey] = $this->data;
            }
        } else {
            if ($this->message !== null) {
                $payload[$messageKey] = $this->message;
            }
            $payload[$dataKey] = $this->data;
        }

        return $payload;
    }

    public function reset(): static
    {
        $this->status          = self::STATUS_SUCCESS;
        $this->code            = 200;
        $this->message         = null;
        $this->data            = null;
        $this->cookies         = [];
        $this->cookiesToRemove = [];

        return $this;
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    protected function setFail(int $code, string $message, mixed $data = null): static
    {
        $this->status  = self::STATUS_FAIL;
        $this->code    = $code;
        $this->message = $message;
        $this->data    = $data !== null ? $this->transformData($data) : null;
        return $this;
    }

    protected function setError(int $code, string $message, mixed $data = null): static
    {
        $this->status  = self::STATUS_ERROR;
        $this->code    = $code;
        $this->message = $message;
        $this->data    = $data !== null ? $this->transformData($data) : null;
        return $this;
    }

    protected function transformData(mixed $data): array
    {
        if ($data instanceof JsonApiResource) {
            return $data->resolve();
        }
        if ($data instanceof JsonResource) {
            return $data->resolve();
        }
        if ($data instanceof Collection) {
            return $data->toArray();
        }
        return (array) $data;
    }
}

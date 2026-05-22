<?php

namespace DantePiazza\LaravelApiResponse\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \DantePiazza\LaravelApiResponse\ApiResponse set(bool $status, int $httpCode, string $message, array|object $data = [])
 * @method static \DantePiazza\LaravelApiResponse\ApiResponse message(string $message)
 * @method static \DantePiazza\LaravelApiResponse\ApiResponse data(array|object $data)
 * @method static \DantePiazza\LaravelApiResponse\ApiResponse records(mixed $records, int $total, int $page = 1, int $pageSize = 10)
 * @method static \DantePiazza\LaravelApiResponse\ApiResponse error(string $error, string $description, ?string $code = null, ?string $field = null)
 * @method static \DantePiazza\LaravelApiResponse\ApiResponse success(array|object $data = [], string $message)
 * @method static \DantePiazza\LaravelApiResponse\ApiResponse created(array|object $data = [], string $message)
 * @method static \DantePiazza\LaravelApiResponse\ApiResponse accepted(array|object $data = [], string $message = 'Accepted')
 * @method static \DantePiazza\LaravelApiResponse\ApiResponse noContent(array|object $data = [], string $message = 'No content')
 * @method static \DantePiazza\LaravelApiResponse\ApiResponse badRequest(array|object $data = [], string $message = 'The request parameters are incorrect.')
 * @method static \DantePiazza\LaravelApiResponse\ApiResponse unauthorized(array|object $data = [], string $message = 'Unauthorized')
 * @method static \DantePiazza\LaravelApiResponse\ApiResponse forbidden(array|object $data = [], string $message = 'Forbidden')
 * @method static \DantePiazza\LaravelApiResponse\ApiResponse notFound(array|object $data = [], string $message = 'Not found')
 * @method static \DantePiazza\LaravelApiResponse\ApiResponse methodNotAllowed(string $message = 'Method not allowed')
 * @method static \DantePiazza\LaravelApiResponse\ApiResponse conflict(string $message = 'Conflict')
 * @method static \DantePiazza\LaravelApiResponse\ApiResponse validationError(string $message = 'Validation failed')
 * @method static \DantePiazza\LaravelApiResponse\ApiResponse tooManyRequests(string $message = 'Too many requests')
 * @method static \DantePiazza\LaravelApiResponse\ApiResponse serverError(string $message = 'Internal server error')
 * @method static \DantePiazza\LaravelApiResponse\ApiResponse serviceUnavailable(string $message = 'Service unavailable')
 * @method static \DantePiazza\LaravelApiResponse\ApiResponse withCookie(mixed $cookie)
 * @method static \DantePiazza\LaravelApiResponse\ApiResponse withoutCookie(string $name)
 * @method static \DantePiazza\LaravelApiResponse\ApiResponse withHeader(mixed $cookie)
 * @method static \DantePiazza\LaravelApiResponse\ApiResponse withHeaders(string $name)
 * @method static \DantePiazza\LaravelApiResponse\ApiResponse reset()
 * @method static array buildPayload()
 * @method static \Illuminate\Http\JsonResponse response()
 *
 * @see \DantePiazza\LaravelApiResponse\ApiResponse
 */
class ApiResponse extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \DantePiazza\LaravelApiResponse\ApiResponse::class;
    }
}

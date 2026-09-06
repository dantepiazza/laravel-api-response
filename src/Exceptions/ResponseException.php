<?php

namespace DantePiazza\LaravelApiResponse\Exceptions;

use Throwable;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use DantePiazza\LaravelApiResponse\Facades\ApiResponse;

class ResponseException extends Exception {
    protected string $error;

    public function __construct(
        string $message = '',
        string $error = '',
        int $code = 400,
        ?Throwable $previous = null
    ) {
        $this->error = $error;
        parent::__construct($message, $code, $previous);
    }

    public function getErrorString(): string
    {
        return $this->error;
    }

    /**
     * Antes SIEMPRE devolvía JSON, sin importar de dónde vino el request —
     * rompía las pantallas Blade de un panel mezclado con API en el mismo
     * MS (ej. gateway: /v1/... es API, pero /settings, /internal/... son
     * formularios normales que esperan un redirect-back-with-errors, no un
     * JSON crudo en la cara). $request->expectsJson() es el mismo criterio
     * que ya usa el resto de Laravel (ValidationException, etc.): true si
     * el caller mandó `Accept: application/json` (todo tráfico MS→MS real
     * lo manda — ver Clousis\Microservices\Services\MicroserviceClient,
     * que lo agrega por default) o si es un request AJAX/fetch. Si no lo
     * pidió, se asume que es un form HTML y se vuelve atrás con el error en
     * la sesión — mismo patrón que ya usan los otros exception handler de
     * la app (ver bootstrap/app.php de gateway, caso TokenMismatchException).
     */
    public function render($request): JsonResponse|RedirectResponse
    {
        if (! $request->expectsJson()) {
            return back()->withInput()->with('error', $this->getMessage());
        }

        $response = ApiResponse::set('fail', $this->getCode(), $this->getMessage());

        if(!empty($this->error)){
            $response->error($this->error, $this->getMessage());
        }

        return $response->response();
    }
}
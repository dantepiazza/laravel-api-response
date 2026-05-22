<?php

namespace DantePiazza\LaravelApiResponse\Exceptions;

use Throwable;
use Exception;
use Illuminate\Http\JsonResponse;
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

    public function render($request): JsonResponse
    {
        $response = ApiResponse::set(false, $this->getCode(), $this->getMessage());

        if(!empty($this->error)){
            $response->error($this->error, $this->getMessage());
        }
        
        return $response->response();
    }
}
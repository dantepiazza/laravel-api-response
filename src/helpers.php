<?php

use DantePiazza\LaravelApiResponse\ApiResponse;

if (! function_exists('api')) {
    /**
     * Return the ApiResponse singleton from the service container.
     *
     * Identical surface API to the original helper so existing code
     * requires zero changes.
     *
     * @return \DantePiazza\LaravelApiResponse\ApiResponse
     */
    function api(): ApiResponse
    {
        return app(ApiResponse::class);
    }
}

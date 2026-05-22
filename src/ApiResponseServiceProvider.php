<?php

namespace DantePiazza\LaravelApiResponse;

use Illuminate\Support\ServiceProvider;

class ApiResponseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/api-response.php',
            'api-response'
        );

        // Singleton so the Facade and the helper always share the same instance,
        // but the instance resets itself after every ->response() call.
        $this->app->singleton(ApiResponse::class, fn () => new ApiResponse());

        $this->app->alias(ApiResponse::class, 'api-response');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/api-response.php' => config_path('api-response.php'),
            ], 'api-response-config');
        }
    }
}

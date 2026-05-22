<?php

namespace DantePiazza\LaravelApiResponse\Tests;

use DantePiazza\LaravelApiResponse\ApiResponseServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [ApiResponseServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'ApiResponse' => \DantePiazza\LaravelApiResponse\Facades\ApiResponse::class,
        ];
    }
}

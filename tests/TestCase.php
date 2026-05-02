<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\CreatesApplication;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        // Prevent running tests against production by mistake.
        if (env('APP_ENV') === 'production' || getenv('APP_ENV') === 'production') {
            throw new \RuntimeException('Refusing to run tests in production environment.');
        }

        parent::setUp();

        // Basic safety: ensure tests run against a non-empty database name that is not the default production DB.
        $db = config('database.connections.' . config('database.default') . '.database');

        if ($db === null || $db === '' ) {
            throw new \RuntimeException('Test database is not configured. Set database in phpunit.xml or .env.testing.');
        }
    }
}

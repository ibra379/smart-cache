<?php

declare(strict_types=1);

namespace Tests;

use DialloIbrahima\SmartCache\SmartCacheServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            SmartCacheServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Use array cache for testing (supports tags)
        config()->set('cache.default', 'array');
        config()->set('smart-cache.enabled', true);
        config()->set('smart-cache.ttl', 60);
        config()->set('smart-cache.logging', false);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../workbench/database/migrations');
    }
}

<?php

declare(strict_types=1);

namespace DialloIbrahima\SmartCache;

use DialloIbrahima\SmartCache\Commands\ClearSmartCacheCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SmartCacheServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('smart-cache')
            ->hasConfigFile()
            ->hasCommand(ClearSmartCacheCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(SmartCacheManager::class, function ($app) {
            return new SmartCacheManager(
                config('smart-cache.driver', 'auto'),
                config('smart-cache.prefix', 'smart_cache'),
                config('smart-cache.ttl', 60),
                config('smart-cache.enabled', true),
                config('smart-cache.logging', false)
            );
        });
    }
}

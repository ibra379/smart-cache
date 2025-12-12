<?php

declare(strict_types=1);

namespace DialloIbrahima\SmartCache;

use DialloIbrahima\SmartCache\Commands\ClearSmartCacheCommand;
use DialloIbrahima\SmartCache\Http\Controllers\SmartCacheDashboardController;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SmartCacheServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('smart-cache')
            ->hasConfigFile()
            ->hasViews()
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

        $this->app->singleton(SmartCacheStats::class, function () {
            return new SmartCacheStats;
        });
    }

    public function packageBooted(): void
    {
        $this->registerDashboardRoutes();
    }

    protected function registerDashboardRoutes(): void
    {
        if (! config('smart-cache.dashboard.enabled', false)) {
            return;
        }

        Route::middleware(config('smart-cache.dashboard.middleware', ['web']))
            ->prefix(config('smart-cache.dashboard.path', 'smart-cache'))
            ->group(function () {
                Route::get('/', [SmartCacheDashboardController::class, 'index'])
                    ->name('smart-cache.dashboard');

                Route::post('/clear', [SmartCacheDashboardController::class, 'clearAll'])
                    ->name('smart-cache.clear-all');

                Route::post('/clear-model', [SmartCacheDashboardController::class, 'clearModel'])
                    ->name('smart-cache.clear-model');
            });
    }
}

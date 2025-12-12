<?php

declare(strict_types=1);

use DialloIbrahima\SmartCache\Http\Controllers\SmartCacheDashboardController;
use DialloIbrahima\SmartCache\SmartCacheManager;
use DialloIbrahima\SmartCache\SmartCacheStats;
use Illuminate\Http\Request;

beforeEach(function () {
    config(['smart-cache.dashboard.enabled' => true]);
});

it('controller can be instantiated', function () {
    $controller = app(SmartCacheDashboardController::class);

    expect($controller)->toBeInstanceOf(SmartCacheDashboardController::class);
});

it('controller index returns view with stats', function () {
    $controller = app(SmartCacheDashboardController::class);

    $view = $controller->index();

    expect($view->getName())->toBe('smart-cache::dashboard');
    expect($view->getData())->toHaveKeys(['stats', 'enabled', 'supportsTags', 'prefix', 'ttl']);
});

it('controller passes correct stats to view', function () {
    /** @var SmartCacheStats $stats */
    $stats = app(SmartCacheStats::class);
    $stats->recordHit('key1', 'posts', 'get');
    $stats->recordHit('key2', 'posts', 'get');
    $stats->recordMiss('key3', 'users', 'first');

    $controller = app(SmartCacheDashboardController::class);
    $view = $controller->index();
    $data = $view->getData();

    expect($data['stats']['hits'])->toBe(2);
    expect($data['stats']['misses'])->toBe(1);
    expect($data['stats']['total'])->toBe(3);
    expect($data['stats']['ratio'])->toBe(66.67);
});

it('controller clearAll resets stats and redirects', function () {
    /** @var SmartCacheStats $stats */
    $stats = app(SmartCacheStats::class);
    $stats->recordHit('key1', 'posts', 'get');

    $controller = app(SmartCacheDashboardController::class);
    $response = $controller->clearAll();

    expect($response->getStatusCode())->toBe(302);
    expect($stats->getHits())->toBe(0);
    expect($stats->getMisses())->toBe(0);
});

it('controller clearModel validates model class exists', function () {
    $controller = app(SmartCacheDashboardController::class);
    $request = Request::create('/clear-model', 'POST', ['model' => 'NonExistent\Model']);

    $response = $controller->clearModel($request);

    expect($response->getStatusCode())->toBe(302);
    expect(session('error'))->not->toBeNull();
});

it('controller clearModel works with valid model', function () {
    $controller = app(SmartCacheDashboardController::class);
    $request = Request::create('/clear-model', 'POST', ['model' => 'Workbench\App\Models\Post']);

    $response = $controller->clearModel($request);

    expect($response->getStatusCode())->toBe(302);
    expect(session('success'))->not->toBeNull();
});

it('controller shows correct configuration values', function () {
    config([
        'smart-cache.enabled' => true,
        'smart-cache.prefix' => 'test_prefix',
        'smart-cache.ttl' => 120,
    ]);

    // Recreate manager with new config
    app()->singleton(SmartCacheManager::class, function () {
        return new SmartCacheManager(
            'auto',
            'test_prefix',
            120,
            true,
            false
        );
    });

    $controller = app(SmartCacheDashboardController::class);
    $view = $controller->index();
    $data = $view->getData();

    expect($data['prefix'])->toBe('test_prefix');
    expect($data['ttl'])->toBe(120);
    expect($data['enabled'])->toBeTrue();
});

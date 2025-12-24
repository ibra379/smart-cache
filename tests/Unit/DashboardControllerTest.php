<?php

declare(strict_types=1);

use DialloIbrahima\SmartCache\Http\Controllers\SmartCacheDashboardController;
use DialloIbrahima\SmartCache\SmartCacheManager;
use DialloIbrahima\SmartCache\SmartCacheStats;

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
    expect($view->getData())->toHaveKeys(['stats', 'enabled', 'supportsTags', 'prefix', 'ttl', 'models']);
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

it('controller clearTable works with valid table', function () {
    $controller = app(SmartCacheDashboardController::class);

    $response = $controller->clearTable('posts');

    expect($response->getStatusCode())->toBe(302);
    expect(session('success'))->not->toBeNull();
});

it('controller clearTable returns success message with table name', function () {
    $controller = app(SmartCacheDashboardController::class);

    $response = $controller->clearTable('users');

    expect($response->getStatusCode())->toBe(302);
    expect(session('success'))->toContain('users');
});

it('controller clearTable handles table names with underscores', function () {
    $controller = app(SmartCacheDashboardController::class);

    $response = $controller->clearTable('user_posts');

    expect($response->getStatusCode())->toBe(302);
    expect(session('success'))->toContain('user_posts');
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

it('controller index includes models in view data', function () {
    $controller = app(SmartCacheDashboardController::class);

    $view = $controller->index();
    $data = $view->getData();

    expect($data)->toHaveKey('models');
    expect($data['models'])->toBeArray();
});

it('controller clearAll redirects to dashboard path', function () {
    config(['smart-cache.dashboard.path' => 'custom-cache-path']);

    $controller = app(SmartCacheDashboardController::class);
    $response = $controller->clearAll();

    expect($response->getTargetUrl())->toContain('custom-cache-path');
});

it('controller clearTable redirects to dashboard path', function () {
    config(['smart-cache.dashboard.path' => 'my-cache']);

    $controller = app(SmartCacheDashboardController::class);
    $response = $controller->clearTable('posts');

    expect($response->getTargetUrl())->toContain('my-cache');
});


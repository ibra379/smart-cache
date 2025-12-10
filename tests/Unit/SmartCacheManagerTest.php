<?php

declare(strict_types=1);

use DialloIbrahima\SmartCache\SmartCacheManager;

it('can be instantiated with config values', function () {
    $manager = new SmartCacheManager(
        driver: 'auto',
        prefix: 'test_cache',
        ttl: 30,
        enabled: true,
        logging: false
    );

    expect($manager->isEnabled())->toBeTrue();
    expect($manager->getPrefix())->toBe('test_cache');
    expect($manager->getTtl())->toBe(30);
});

it('can be disabled', function () {
    $manager = new SmartCacheManager(
        driver: 'auto',
        prefix: 'smart_cache',
        ttl: 60,
        enabled: false,
        logging: false
    );

    expect($manager->isEnabled())->toBeFalse();
});

it('executes callback directly when disabled', function () {
    $manager = new SmartCacheManager(
        driver: 'auto',
        prefix: 'smart_cache',
        ttl: 60,
        enabled: false,
        logging: false
    );

    $callCount = 0;
    $result = $manager->remember('test_key', [], 60, function () use (&$callCount) {
        $callCount++;

        return 'result';
    });

    expect($result)->toBe('result');
    expect($callCount)->toBe(1);
});

it('detects tag support correctly', function () {
    $manager = app(SmartCacheManager::class);

    // Array cache supports tags
    expect($manager->supportsTags())->toBeTrue();
});

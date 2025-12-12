<?php

declare(strict_types=1);

use DialloIbrahima\SmartCache\SmartCacheStats;

it('records cache hits', function () {
    $stats = new SmartCacheStats;

    $stats->recordHit('key1', 'posts', 'get');
    $stats->recordHit('key2', 'users', 'first');

    expect($stats->getHits())->toBe(2);
    expect($stats->getMisses())->toBe(0);
});

it('records cache misses', function () {
    $stats = new SmartCacheStats;

    $stats->recordMiss('key1', 'posts', 'get');

    expect($stats->getHits())->toBe(0);
    expect($stats->getMisses())->toBe(1);
});

it('calculates total requests', function () {
    $stats = new SmartCacheStats;

    $stats->recordHit('key1', 'posts', 'get');
    $stats->recordHit('key2', 'posts', 'get');
    $stats->recordMiss('key3', 'posts', 'get');

    expect($stats->getTotal())->toBe(3);
});

it('calculates hit ratio correctly', function () {
    $stats = new SmartCacheStats;

    $stats->recordHit('key1', 'posts', 'get');
    $stats->recordHit('key2', 'posts', 'get');
    $stats->recordHit('key3', 'posts', 'get');
    $stats->recordMiss('key4', 'posts', 'get');

    expect($stats->getHitRatio())->toBe(75.0);
});

it('returns zero ratio when no requests', function () {
    $stats = new SmartCacheStats;

    expect($stats->getHitRatio())->toBe(0.0);
});

it('tracks recent queries', function () {
    $stats = new SmartCacheStats;

    $stats->recordHit('key1', 'posts', 'get');
    $stats->recordMiss('key2', 'users', 'first');

    $queries = $stats->getQueries();

    expect($queries)->toHaveCount(2);
    expect($queries[0]['table'])->toBe('users');  // Most recent first
    expect($queries[0]['status'])->toBe('miss');
    expect($queries[1]['table'])->toBe('posts');
    expect($queries[1]['status'])->toBe('hit');
});

it('can reset all stats', function () {
    $stats = new SmartCacheStats;

    $stats->recordHit('key1', 'posts', 'get');
    $stats->recordMiss('key2', 'users', 'first');
    $stats->reset();

    expect($stats->getHits())->toBe(0);
    expect($stats->getMisses())->toBe(0);
    expect($stats->getQueries())->toBeEmpty();
});

it('converts to array correctly', function () {
    $stats = new SmartCacheStats;

    $stats->recordHit('key1', 'posts', 'get');
    $stats->recordMiss('key2', 'posts', 'get');

    $array = $stats->toArray();

    expect($array)->toHaveKeys(['hits', 'misses', 'total', 'ratio', 'queries']);
    expect($array['hits'])->toBe(1);
    expect($array['misses'])->toBe(1);
    expect($array['total'])->toBe(2);
    expect($array['ratio'])->toBe(50.0);
});

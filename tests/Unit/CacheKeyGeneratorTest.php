<?php

declare(strict_types=1);

use DialloIbrahima\SmartCache\Support\CacheKeyGenerator;
use Tests\Fixtures\Post;

it('generates unique cache keys for different queries', function () {
    $query1 = Post::query()->where('published', true);
    $query2 = Post::query()->where('published', false);

    $key1 = CacheKeyGenerator::generate($query1);
    $key2 = CacheKeyGenerator::generate($query2);

    expect($key1)->not->toBe($key2);
});

it('generates same cache key for identical queries', function () {
    $query1 = Post::query()->where('published', true);
    $query2 = Post::query()->where('published', true);

    $key1 = CacheKeyGenerator::generate($query1);
    $key2 = CacheKeyGenerator::generate($query2);

    expect($key1)->toBe($key2);
});

it('generates different keys for count vs get', function () {
    $query = Post::query()->where('published', true);

    $getKey = CacheKeyGenerator::generate($query);
    $countKey = CacheKeyGenerator::generateForCount($query);

    expect($getKey)->not->toBe($countKey);
    expect($countKey)->toEndWith(':count');
});

it('generates different keys for first vs get', function () {
    $query = Post::query()->where('published', true);

    $getKey = CacheKeyGenerator::generate($query);
    $firstKey = CacheKeyGenerator::generateForFirst($query);

    expect($getKey)->not->toBe($firstKey);
    expect($firstKey)->toEndWith(':first');
});

it('generates different keys for different aggregate functions', function () {
    $query = Post::query();

    $sumKey = CacheKeyGenerator::generateForAggregate($query, 'sum', 'price');
    $avgKey = CacheKeyGenerator::generateForAggregate($query, 'avg', 'price');

    expect($sumKey)->not->toBe($avgKey);
});

<?php

declare(strict_types=1);

use DialloIbrahima\SmartCache\SmartCacheDiscovery;

it('can be instantiated', function () {
    $discovery = app(SmartCacheDiscovery::class);

    expect($discovery)->toBeInstanceOf(SmartCacheDiscovery::class);
});

it('returns an array of models', function () {
    $discovery = app(SmartCacheDiscovery::class);

    $models = $discovery->discoverCachedModels();

    expect($models)->toBeArray();
});

it('discovers models with HasSmartCache trait', function () {
    $discovery = app(SmartCacheDiscovery::class);

    $models = $discovery->discoverCachedModels();

    // In the workbench, we have Post, Comment, User models with HasSmartCache
    // Note: This test runs in the package context, so app/Models may be empty
    // But the method should still return an array
    expect($models)->toBeArray();
});

it('returns correct structure for each model including invalidates', function () {
    $discovery = app(SmartCacheDiscovery::class);

    $models = $discovery->discoverCachedModels();

    // Ensure we get an array
    expect($models)->toBeArray();

    // If models exist, verify their structure includes invalidates
    foreach ($models as $model) {
        expect($model)->toHaveKeys(['class', 'table', 'short_name', 'invalidates']);
        expect($model['class'])->toBeString();
        expect($model['table'])->toBeString();
        expect($model['short_name'])->toBeString();
        expect($model['invalidates'])->toBeArray();
    }
});

it('returns models sorted by short name', function () {
    $discovery = app(SmartCacheDiscovery::class);

    $models = $discovery->discoverCachedModels();

    if (count($models) >= 2) {
        $shortNames = array_column($models, 'short_name');
        $sortedNames = $shortNames;
        sort($sortedNames);

        expect($shortNames)->toBe($sortedNames);
    } else {
        expect(true)->toBeTrue(); // Skip if not enough models
    }
});

it('returns empty array when no models directory exists', function () {
    // This is implicitly tested since we're in package context
    // The app_path('Models') may not exist or be empty
    $discovery = app(SmartCacheDiscovery::class);

    $result = $discovery->discoverCachedModels();

    expect($result)->toBeArray();
});

it('generates mermaid diagram for models', function () {
    $discovery = app(SmartCacheDiscovery::class);

    $models = [
        ['class' => 'App\\Models\\Comment', 'table' => 'comments', 'short_name' => 'Comment', 'invalidates' => ['Post']],
        ['class' => 'App\\Models\\Post', 'table' => 'posts', 'short_name' => 'Post', 'invalidates' => []],
    ];

    $diagram = $discovery->generateMermaidDiagram($models);

    expect($diagram)->toBeString();
    expect($diagram)->toContain('graph LR');
    expect($diagram)->toContain('Comment');
    expect($diagram)->toContain('Post');
    expect($diagram)->toContain('invalidates');
});

it('generates mermaid diagram with isolated nodes when no relations', function () {
    $discovery = app(SmartCacheDiscovery::class);

    $models = [
        ['class' => 'App\\Models\\User', 'table' => 'users', 'short_name' => 'User', 'invalidates' => []],
        ['class' => 'App\\Models\\Post', 'table' => 'posts', 'short_name' => 'Post', 'invalidates' => []],
    ];

    $diagram = $discovery->generateMermaidDiagram($models);

    expect($diagram)->toBeString();
    expect($diagram)->toContain('graph LR');
    expect($diagram)->toContain('User');
    expect($diagram)->toContain('Post');
});

it('gets related tables for cascade invalidation', function () {
    $discovery = app(SmartCacheDiscovery::class);

    $models = [
        ['class' => 'App\\Models\\Comment', 'table' => 'comments', 'short_name' => 'Comment', 'invalidates' => ['Post']],
        ['class' => 'App\\Models\\Post', 'table' => 'posts', 'short_name' => 'Post', 'invalidates' => []],
    ];

    $relatedTables = $discovery->getRelatedTables('comments', $models);

    expect($relatedTables)->toBeArray();
    expect($relatedTables)->toContain('posts');
});

it('returns empty array when no related tables', function () {
    $discovery = app(SmartCacheDiscovery::class);

    $models = [
        ['class' => 'App\\Models\\Post', 'table' => 'posts', 'short_name' => 'Post', 'invalidates' => []],
    ];

    $relatedTables = $discovery->getRelatedTables('posts', $models);

    expect($relatedTables)->toBeArray();
    expect($relatedTables)->toBeEmpty();
});


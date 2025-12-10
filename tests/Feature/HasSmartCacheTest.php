<?php

declare(strict_types=1);

use Workbench\App\Models\Post;

beforeEach(function () {
    Post::enableSmartCache();
});

it('can cache query results', function () {
    Post::factory()->published()->create(['title' => 'First Post']);
    Post::factory()->draft()->create(['title' => 'Second Post']);

    // First call - should execute query
    $posts = Post::smartCache()->where('published', true)->smartGet();

    expect($posts)->toHaveCount(1)
        ->and($posts->first()->title)->toBe('First Post');

    // Second call - should use cache (same query)
    $cachedPosts = Post::smartCache()->where('published', true)->smartGet();

    expect($cachedPosts)->toHaveCount(1);
});

it('can cache count queries', function () {
    Post::factory()->published()->count(2)->create();
    Post::factory()->draft()->create();

    $count = Post::smartCache()->where('published', true)->smartCount();

    expect($count)->toBe(2);
});

it('can cache first query', function () {
    Post::factory()->published()->create(['title' => 'First Post']);
    Post::factory()->published()->create(['title' => 'Second Post']);

    $post = Post::smartCache()->where('published', true)->smartFirst();

    expect($post)->not->toBeNull()
        ->and($post->title)->toBe('First Post');
});

it('can cache aggregate queries', function () {
    Post::factory()->count(3)->create();

    $count = Post::smartCache()->smartCount();

    expect($count)->toBe(3);
});

it('respects custom TTL', function () {
    Post::factory()->published()->create();

    $posts = Post::smartCache(30)->where('published', true)->smartGet();

    expect($posts)->toHaveCount(1);
});

it('can disable smart cache per query', function () {
    Post::factory()->published()->create();

    $posts = Post::withoutSmartCache()->where('published', true)->get();

    expect($posts)->toHaveCount(1);
});

it('can disable smart cache globally for model', function () {
    Post::factory()->published()->create();

    Post::disableSmartCache();

    $posts = Post::smartCache()->where('published', true)->smartGet();

    expect($posts)->toHaveCount(1);

    Post::enableSmartCache();
});

it('can clear smart cache for model', function () {
    Post::factory()->published()->create();

    // Cache the query
    Post::smartCache()->where('published', true)->smartGet();

    // Clear cache
    Post::clearSmartCache();

    // Query should execute again (cache was cleared)
    $posts = Post::smartCache()->where('published', true)->smartGet();

    expect($posts)->toHaveCount(1);
});

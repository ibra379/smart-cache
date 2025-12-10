<?php

declare(strict_types=1);

use Tests\Fixtures\Post;

beforeEach(function () {
    Post::enableSmartCache();
});

it('can cache query results', function () {
    Post::create(['title' => 'First Post', 'content' => 'Content 1', 'published' => true]);
    Post::create(['title' => 'Second Post', 'content' => 'Content 2', 'published' => false]);

    // First call - should execute query
    $posts = Post::smartCache()->where('published', true)->smartGet();

    expect($posts)->toHaveCount(1)
        ->and($posts->first()->title)->toBe('First Post');

    // Second call - should use cache (same query)
    $cachedPosts = Post::smartCache()->where('published', true)->smartGet();

    expect($cachedPosts)->toHaveCount(1);
});

it('can cache count queries', function () {
    Post::create(['title' => 'First Post', 'published' => true]);
    Post::create(['title' => 'Second Post', 'published' => true]);
    Post::create(['title' => 'Third Post', 'published' => false]);

    $count = Post::smartCache()->where('published', true)->smartCount();

    expect($count)->toBe(2);
});

it('can cache first query', function () {
    Post::create(['title' => 'First Post', 'published' => true]);
    Post::create(['title' => 'Second Post', 'published' => true]);

    $post = Post::smartCache()->where('published', true)->smartFirst();

    expect($post)->not->toBeNull()
        ->and($post->title)->toBe('First Post');
});

it('can cache aggregate queries', function () {
    Post::create(['title' => 'Post 1', 'published' => true]);
    Post::create(['title' => 'Post 22', 'published' => true]);
    Post::create(['title' => 'Post 333', 'published' => true]);

    $count = Post::smartCache()->smartCount();

    expect($count)->toBe(3);
});

it('respects custom TTL', function () {
    Post::create(['title' => 'Test Post', 'published' => true]);

    $posts = Post::smartCache(30)->where('published', true)->smartGet();

    expect($posts)->toHaveCount(1);
});

it('can disable smart cache per query', function () {
    Post::create(['title' => 'Test Post', 'published' => true]);

    $posts = Post::withoutSmartCache()->where('published', true)->get();

    expect($posts)->toHaveCount(1);
});

it('can disable smart cache globally for model', function () {
    Post::create(['title' => 'Test Post', 'published' => true]);

    Post::disableSmartCache();

    $posts = Post::smartCache()->where('published', true)->smartGet();

    expect($posts)->toHaveCount(1);

    Post::enableSmartCache();
});

it('can clear smart cache for model', function () {
    Post::create(['title' => 'Test Post', 'published' => true]);

    // Cache the query
    Post::smartCache()->where('published', true)->smartGet();

    // Clear cache
    Post::clearSmartCache();

    // Query should execute again (cache was cleared)
    $posts = Post::smartCache()->where('published', true)->smartGet();

    expect($posts)->toHaveCount(1);
});

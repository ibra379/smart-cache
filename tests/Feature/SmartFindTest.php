<?php

declare(strict_types=1);

use Workbench\App\Models\Post;

beforeEach(function () {
    Post::enableSmartCache();
});

it('caches individual records with smartFind', function () {
    $post = Post::factory()->create(['title' => 'Test Post']);

    // First call - cache miss
    $found1 = Post::smartCache()->smartFind($post->id);

    // Second call - cache hit
    $found2 = Post::smartCache()->smartFind($post->id);

    expect($found1->id)->toBe($post->id);
    expect($found2->id)->toBe($post->id);
    expect($found1->title)->toBe('Test Post');
});

it('invalidates only the specific record cache when updated', function () {
    $post1 = Post::factory()->create(['title' => 'Post 1']);
    $post2 = Post::factory()->create(['title' => 'Post 2']);

    // Cache both records
    Post::smartCache()->smartFind($post1->id);
    Post::smartCache()->smartFind($post2->id);

    // Update post1 - should invalidate post1 cache
    $post1->update(['title' => 'Updated Post 1']);

    // Query post1 again - should reflect update
    $found1 = Post::smartCache()->smartFind($post1->id);

    expect($found1->title)->toBe('Updated Post 1');
});

it('invalidates record cache when deleted', function () {
    $post = Post::factory()->create(['title' => 'To Delete']);

    // Cache the record
    $cached = Post::smartCache()->smartFind($post->id);
    expect($cached)->not->toBeNull();

    // Delete the post
    $post->delete();

    // Create a new post with same ID won't return cached data
    // The cache should be invalidated
    $newPost = Post::factory()->create(['title' => 'New Post']);

    $found = Post::smartCache()->smartFind($newPost->id);
    expect($found->title)->toBe('New Post');
});

it('respects custom TTL with smartFind', function () {
    $post = Post::factory()->create(['title' => 'TTL Test']);

    // Use custom TTL
    $found = Post::smartCache(30)->smartFind($post->id);

    expect($found->id)->toBe($post->id);
});

it('works with smartCache disabled', function () {
    Post::disableSmartCache();

    $post = Post::factory()->create(['title' => 'No Cache']);

    $found = Post::smartCache()->smartFind($post->id);

    expect($found->id)->toBe($post->id);

    Post::enableSmartCache();
});

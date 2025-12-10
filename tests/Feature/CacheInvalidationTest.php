<?php

declare(strict_types=1);

use Workbench\App\Models\Post;

beforeEach(function () {
    Post::enableSmartCache();
});

it('invalidates cache when model is created', function () {
    // Cache query (empty result)
    Post::smartCache()->smartGet();

    // Create should invalidate
    $post = Post::factory()->create(['title' => 'New Post']);

    // Query again - should now include new post
    $posts = Post::smartCache()->smartGet();

    expect($posts)->toHaveCount(1)
        ->and($posts->first()->title)->toBe('New Post');
});

it('invalidates cache when model is updated', function () {
    $post = Post::factory()->published()->create(['title' => 'Original Title']);

    // Cache query
    $cachedPosts = Post::smartCache()->where('published', true)->smartGet();
    expect($cachedPosts->first()->title)->toBe('Original Title');

    // Update should invalidate
    $post->update(['title' => 'Updated Title']);

    // Query again - should reflect update
    $posts = Post::smartCache()->where('published', true)->smartGet();

    expect($posts->first()->title)->toBe('Updated Title');
});

it('invalidates cache when model is deleted', function () {
    $post1 = Post::factory()->create(['title' => 'Post 1']);
    $post2 = Post::factory()->create(['title' => 'Post 2']);

    // Cache query
    $cachedPosts = Post::smartCache()->smartGet();
    expect($cachedPosts)->toHaveCount(2);

    // Delete should invalidate
    $post1->delete();

    // Query again - should reflect deletion
    $posts = Post::smartCache()->smartGet();

    expect($posts)->toHaveCount(1)
        ->and($posts->first()->title)->toBe('Post 2');
});

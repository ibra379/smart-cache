<?php

declare(strict_types=1);

use Tests\Fixtures\Post;

beforeEach(function () {
    Post::enableSmartCache();
});

it('invalidates cache when model is created', function () {
    // Cache query
    Post::smartCache()->smartGet();

    // Create should invalidate
    Post::create(['title' => 'New Post', 'published' => true]);

    // Query again - should now include new post
    $posts = Post::smartCache()->smartGet();

    expect($posts)->toHaveCount(1)
        ->and($posts->first()->title)->toBe('New Post');
});

it('invalidates cache when model is updated', function () {
    $post = Post::create(['title' => 'Original Title', 'published' => true]);

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
    $post1 = Post::create(['title' => 'Post 1', 'published' => true]);
    $post2 = Post::create(['title' => 'Post 2', 'published' => true]);

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

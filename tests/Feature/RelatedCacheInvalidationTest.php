<?php

declare(strict_types=1);

use Workbench\App\Models\Comment;
use Workbench\App\Models\Post;

beforeEach(function () {
    Post::enableSmartCache();
});

it('invalidates related model cache when model with invalidatesSmartCacheOf changes', function () {
    // Create a post
    $post = Post::factory()->create(['title' => 'Original Post']);

    // Cache the posts query
    $cachedPosts = Post::smartCache()->smartGet();
    expect($cachedPosts)->toHaveCount(1);

    // Create a comment - this should invalidate Post cache because Comment::invalidatesSmartCacheOf() returns [Post::class]
    Comment::factory()->create(['post_id' => $post->id, 'body' => 'Test comment']);

    // Create a new post to verify cache was invalidated
    Post::factory()->create(['title' => 'New Post']);

    // Query posts again - should reflect the new post (cache was invalidated)
    $posts = Post::smartCache()->smartGet();

    expect($posts)->toHaveCount(2);
});

it('invalidates related model cache when model is updated', function () {
    $post = Post::factory()->create(['title' => 'Test Post']);
    $comment = Comment::factory()->create(['post_id' => $post->id, 'body' => 'Original body']);

    // Cache the posts query
    Post::smartCache()->smartGet();

    // Update the comment - should invalidate Post cache
    $comment->update(['body' => 'Updated body']);

    // Create a new post
    Post::factory()->create(['title' => 'Another Post']);

    // Query posts - should reflect the new post
    $posts = Post::smartCache()->smartGet();

    expect($posts)->toHaveCount(2);
});

it('invalidates related model cache when model is deleted', function () {
    $post = Post::factory()->create(['title' => 'Test Post']);
    $comment = Comment::factory()->create(['post_id' => $post->id, 'body' => 'Test body']);

    // Cache the posts query
    Post::smartCache()->smartGet();

    // Delete the comment - should invalidate Post cache
    $comment->delete();

    // Create a new post
    Post::factory()->create(['title' => 'New Post']);

    // Query posts - should reflect the new post
    $posts = Post::smartCache()->smartGet();

    expect($posts)->toHaveCount(2);
});

it('model without invalidatesSmartCacheOf only invalidates its own cache', function () {
    // Post doesn't define invalidatesSmartCacheOf, so it only invalidates its own cache
    $post = Post::factory()->create(['title' => 'Original']);

    // Cache both queries
    Post::smartCache()->smartGet();
    Comment::factory()->create(['post_id' => $post->id]);
    Comment::smartCache()->smartGet();

    // Update post - should NOT invalidate Comment cache
    $post->update(['title' => 'Updated']);

    // Comment cache should still be valid (1 comment)
    $comments = Comment::smartCache()->smartGet();
    expect($comments)->toHaveCount(1);
});

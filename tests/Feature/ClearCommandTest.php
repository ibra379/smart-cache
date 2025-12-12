<?php

declare(strict_types=1);

use DialloIbrahima\SmartCache\Commands\ClearSmartCacheCommand;
use Workbench\App\Models\Post;

beforeEach(function () {
    Post::enableSmartCache();
});

it('command shows help text when no arguments provided', function () {
    $this->artisan('smart-cache:clear')
        ->expectsOutput('Please specify a model class or use --all to clear all cache.')
        ->assertExitCode(1);
});

it('command clears cache for valid model', function () {
    // Create and cache a post
    $post = Post::factory()->create(['title' => 'Test']);
    Post::smartCache()->smartGet();

    $this->artisan('smart-cache:clear', ['model' => 'Workbench\App\Models\Post'])
        ->assertExitCode(0);
});

it('command fails for non-existent model', function () {
    $this->artisan('smart-cache:clear', ['model' => 'NonExistent\Model'])
        ->expectsOutput('Class NonExistent\Model does not exist.')
        ->assertExitCode(1);
});

it('command fails for non-model class', function () {
    $this->artisan('smart-cache:clear', ['model' => 'stdClass'])
        ->expectsOutput('stdClass is not an Eloquent model.')
        ->assertExitCode(1);
});

it('command clears all cache with --all flag', function () {
    // Create and cache posts
    Post::factory()->count(3)->create();
    Post::smartCache()->smartGet();

    $this->artisan('smart-cache:clear', ['--all' => true])
        ->assertExitCode(0);
});

<?php

declare(strict_types=1);

namespace DialloIbrahima\SmartCache\Commands;

use DialloIbrahima\SmartCache\SmartCacheManager;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

class ClearSmartCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'smart-cache:clear 
                            {model? : The model class to clear cache for (e.g., App\\Models\\User)}
                            {--all : Clear all SmartCache entries}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear SmartCache entries for a specific model or all models';

    /**
     * Execute the console command.
     */
    public function handle(SmartCacheManager $cacheManager): int
    {
        if (! $cacheManager->supportsTags()) {
            $this->error('Your cache driver does not support tags. SmartCache requires Redis, Memcached, or Array driver.');

            return self::FAILURE;
        }

        $model = $this->argument('model');
        $clearAll = $this->option('all');

        if ($clearAll) {
            return $this->clearAllCache($cacheManager);
        }

        if ($model) {
            return $this->clearModelCache($cacheManager, $model);
        }

        $this->error('Please specify a model class or use --all to clear all cache.');
        $this->line('');
        $this->line('Usage:');
        $this->line('  php artisan smart-cache:clear App\\Models\\User');
        $this->line('  php artisan smart-cache:clear --all');

        return self::FAILURE;
    }

    /**
     * Clear cache for a specific model.
     */
    protected function clearModelCache(SmartCacheManager $cacheManager, string $modelClass): int
    {
        if (! class_exists($modelClass)) {
            $this->error("Class {$modelClass} does not exist.");

            return self::FAILURE;
        }

        $instance = new $modelClass;

        if (! $instance instanceof Model) {
            $this->error("{$modelClass} is not an Eloquent model.");

            return self::FAILURE;
        }

        $table = $instance->getTable();
        $prefix = $cacheManager->getPrefix();
        $tag = $prefix.'.'.$table;

        $cacheManager->invalidateTags([$tag]);

        $this->info("✓ SmartCache cleared for {$modelClass} (tag: {$tag})");

        return self::SUCCESS;
    }

    /**
     * Clear all SmartCache entries.
     */
    protected function clearAllCache(SmartCacheManager $cacheManager): int
    {
        $prefix = $cacheManager->getPrefix();

        // Note: This clears the entire cache store with the prefix tag
        // For a more targeted approach, you'd need to track all model tags
        $cacheManager->invalidateTags([$prefix]);

        $this->info('✓ All SmartCache entries cleared.');

        return self::SUCCESS;
    }
}

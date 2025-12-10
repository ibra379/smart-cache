<?php

declare(strict_types=1);

namespace DialloIbrahima\SmartCache\Observers;

use DialloIbrahima\SmartCache\SmartCacheManager;
use Illuminate\Database\Eloquent\Model;

class SmartCacheObserver
{
    public function __construct(
        protected SmartCacheManager $cacheManager
    ) {}

    /**
     * Handle the Model "created" event.
     */
    public function created(Model $model): void
    {
        $this->invalidateCache($model);
    }

    /**
     * Handle the Model "updated" event.
     */
    public function updated(Model $model): void
    {
        $this->invalidateCache($model);
    }

    /**
     * Handle the Model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        $this->invalidateCache($model);
    }

    /**
     * Handle the Model "restored" event.
     */
    public function restored(Model $model): void
    {
        $this->invalidateCache($model);
    }

    /**
     * Handle the Model "forceDeleted" event.
     */
    public function forceDeleted(Model $model): void
    {
        $this->invalidateCache($model);
    }

    /**
     * Invalidate cache for the model's table.
     */
    protected function invalidateCache(Model $model): void
    {
        $table = $model->getTable();
        $prefix = $this->cacheManager->getPrefix();

        $this->cacheManager->invalidateTags([$prefix.'.'.$table]);
    }
}

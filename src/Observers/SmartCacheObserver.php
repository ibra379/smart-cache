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
     * Invalidate cache for the model's table and related models.
     */
    protected function invalidateCache(Model $model): void
    {
        $table = $model->getTable();
        $prefix = $this->cacheManager->getPrefix();

        // Invalidate record-level cache (for smartFind)
        $id = $model->getKey();
        if ($id !== null) {
            $this->cacheManager->invalidateTags([$prefix.'.'.$table.'.'.$id]);
        }

        // Invalidate table-level cache (for smartGet, smartFirst, etc.)
        $this->cacheManager->invalidateTags([$prefix.'.'.$table]);

        // Invalidate related model caches
        if (method_exists($model, 'invalidatesSmartCacheOf')) {
            /** @var array<class-string<Model>> $relatedModels */
            $relatedModels = $model::invalidatesSmartCacheOf();

            foreach ($relatedModels as $relatedClass) {
                /** @var Model $relatedInstance */
                $relatedInstance = new $relatedClass;
                $relatedTable = $relatedInstance->getTable();
                $this->cacheManager->invalidateTags([$prefix.'.'.$relatedTable]);
            }
        }
    }
}

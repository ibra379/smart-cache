<?php

declare(strict_types=1);

namespace DialloIbrahima\SmartCache;

use DialloIbrahima\SmartCache\Observers\SmartCacheObserver;
use DialloIbrahima\SmartCache\Support\CacheKeyGenerator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait for Eloquent models to enable smart caching.
 *
 * @mixin Model
 *
 * @method static \Illuminate\Database\Eloquent\Builder smartCache(?int $ttl = null)
 * @method static \Illuminate\Database\Eloquent\Builder withoutSmartCache()
 * @method \Illuminate\Database\Eloquent\Collection smartGet(array|string $columns = ['*'])
 * @method static|null smartFirst(array|string $columns = ['*'])
 * @method static|null smartFind(int|string $id, array|string $columns = ['*'])
 * @method int smartCount(string $columns = '*')
 * @method float|int smartSum(string $column)
 * @method float|int|null smartAvg(string $column)
 * @method mixed smartMax(string $column)
 * @method mixed smartMin(string $column)
 */
trait HasSmartCache
{
    protected static bool $smartCacheEnabled = true;

    protected ?int $smartCacheTtl = null;

    /**
     * Boot the trait and register the observer.
     */
    public static function bootHasSmartCache(): void
    {
        static::observe(SmartCacheObserver::class);
    }

    /**
     * Scope to enable smart caching for this query.
     *
     * @param  int|null  $ttl  Cache TTL in minutes (null = use config default)
     */
    public function scopeSmartCache(Builder $query, ?int $ttl = null): Builder
    {
        if (! static::$smartCacheEnabled || ! config('smart-cache.enabled', true)) {
            return $query;
        }

        // Store TTL in a query for later use
        $query->getModel()->smartCacheTtl = $ttl;

        return $query;
    }

    /**
     * Scope to disable smart caching for this query.
     */
    public function scopeWithoutSmartCache(Builder $query): Builder
    {
        $query->getModel()->smartCacheTtl = null;
        static::$smartCacheEnabled = false;

        return $query;
    }

    /**
     * Get results with smart caching.
     *
     * @param  array<string>|string  $columns
     * @return Collection<int, static>
     */
    public function scopeSmartGet(Builder $query, array|string $columns = ['*']): Collection
    {
        $columns = is_array($columns) ? $columns : func_get_args();

        return $this->executeWithSmartCache($query, function () use ($query, $columns) {
            return $query->get($columns);
        });
    }

    /**
     * Get first result with smart caching.
     *
     * @param  array<string>|string  $columns
     */
    public function scopeSmartFirst(Builder $query, array|string $columns = ['*']): ?Model
    {
        $columns = is_array($columns) ? $columns : func_get_args();

        return $this->executeWithSmartCache($query, function () use ($query, $columns) {
            return $query->first($columns);
        }, 'first');
    }

    /**
     * Find a model by its primary key with smart caching.
     * Uses record-level cache tags for granular invalidation.
     *
     * @param  int|string  $id
     * @param  array<string>|string  $columns
     * @return static|null
     */
    public function scopeSmartFind(Builder $query, int|string $id, array|string $columns = ['*']): ?Model
    {
        $columns = is_array($columns) ? $columns : [$columns];

        return $this->executeWithSmartCacheById($query, $id, function () use ($query, $id, $columns) {
            return $query->find($id, $columns);
        });
    }

    /**
     * Get count with smart caching.
     */
    public function scopeSmartCount(Builder $query, string $columns = '*'): int
    {
        return $this->executeWithSmartCache($query, function () use ($query, $columns) {
            return $query->count($columns);
        }, 'count');
    }

    /**
     * Get sum with smart caching.
     */
    public function scopeSmartSum(Builder $query, string $column): float|int
    {
        return $this->executeWithSmartCache($query, function () use ($query, $column) {
            return $query->sum($column);
        }, 'sum', $column);
    }

    /**
     * Get average with smart caching.
     */
    public function scopeSmartAvg(Builder $query, string $column): float|int|null
    {
        return $this->executeWithSmartCache($query, function () use ($query, $column) {
            return $query->avg($column);
        }, 'avg', $column);
    }

    /**
     * Get max with smart caching.
     */
    public function scopeSmartMax(Builder $query, string $column): mixed
    {
        return $this->executeWithSmartCache($query, function () use ($query, $column) {
            return $query->max($column);
        }, 'max', $column);
    }

    /**
     * Get min with smart caching.
     */
    public function scopeSmartMin(Builder $query, string $column): mixed
    {
        return $this->executeWithSmartCache($query, function () use ($query, $column) {
            return $query->min($column);
        }, 'min', $column);
    }

    /**
     * Execute a query with smart caching.
     */
    protected function executeWithSmartCache(
        Builder $query,
        callable $callback,
        string $type = 'get',
        ?string $column = null
    ): mixed {
        /** @var SmartCacheManager $cacheManager */
        $cacheManager = app(SmartCacheManager::class);

        if (! $cacheManager->isEnabled() || ! static::$smartCacheEnabled) {
            return $callback();
        }

        // Get TTL from model or use config default
        $ttl = $query->getModel()->smartCacheTtl ?? $cacheManager->getTtl();

        // Generate cache key
        $cacheKey = match ($type) {
            'count' => CacheKeyGenerator::generateForCount($query),
            'first' => CacheKeyGenerator::generateForFirst($query),
            'sum', 'avg', 'max', 'min' => CacheKeyGenerator::generateForAggregate($query, $type, $column ?? ''),
            default => CacheKeyGenerator::generate($query),
        };

        $table = $query->getModel()->getTable();
        $prefix = $cacheManager->getPrefix();
        $tags = [$prefix.'.'.$table];

        return $cacheManager->remember($cacheKey, $tags, $ttl, $callback, $table, $type);
    }

    /**
     * Execute a query with smart caching using record-level tags.
     * This allows for granular invalidation when a specific record changes.
     */
    protected function executeWithSmartCacheById(
        Builder $query,
        int|string $id,
        callable $callback
    ): mixed {
        /** @var SmartCacheManager $cacheManager */
        $cacheManager = app(SmartCacheManager::class);

        if (! $cacheManager->isEnabled() || ! static::$smartCacheEnabled) {
            return $callback();
        }

        // Get TTL from model or use config default
        $ttl = $query->getModel()->smartCacheTtl ?? $cacheManager->getTtl();

        // Generate cache key based on table and ID
        $table = $query->getModel()->getTable();
        $prefix = $cacheManager->getPrefix();
        $cacheKey = $prefix.'.'.$table.'.find.'.$id;

        // Use BOTH table-level and record-level tags
        // Table tag: for clearing all cache of this model
        // Record tag: for granular invalidation
        $tags = [
            $prefix.'.'.$table,           // Table-level tag
            $prefix.'.'.$table.'.'.$id,   // Record-level tag
        ];

        return $cacheManager->remember($cacheKey, $tags, $ttl, $callback, $table, 'find');
    }

    /**
     * Disable smart cache for this model.
     */
    public static function disableSmartCache(): void
    {
        static::$smartCacheEnabled = false;
    }

    /**
     * Enable smart cache for this model.
     */
    public static function enableSmartCache(): void
    {
        static::$smartCacheEnabled = true;
    }

    /**
     * Clear all cached queries for this model.
     */
    public static function clearSmartCache(): void
    {
        /** @var SmartCacheManager $cacheManager */
        $cacheManager = app(SmartCacheManager::class);

        $table = (new static)->getTable();
        $prefix = $cacheManager->getPrefix();

        $cacheManager->invalidateTags([$prefix.'.'.$table]);
    }

    /**
     * Get the list of model classes whose cache should be invalidated when this model changes.
     *
     * Override this method in your model to define related cache invalidation.
     *
     * Example:
     * ```php
     * public static function invalidatesSmartCacheOf(): array
     * {
     *     return [Notification::class, Dashboard::class];
     * }
     * ```
     *
     * @return array<class-string<Model>>
     */
    public static function invalidatesSmartCacheOf(): array
    {
        return [];
    }
}

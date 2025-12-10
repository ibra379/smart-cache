<?php

declare(strict_types=1);

namespace DialloIbrahima\SmartCache;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SmartCacheManager
{
    protected Repository $cache;

    public function __construct(
        protected string $driver,
        protected string $prefix,
        protected int $ttl,
        protected bool $enabled,
        protected bool $logging
    ) {
        $this->cache = $this->resolveCache();
    }

    protected function resolveCache(): Repository
    {
        if ($this->driver === 'auto') {
            return Cache::store();
        }

        return Cache::store($this->driver);
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function getTtl(): int
    {
        return $this->ttl;
    }

    public function getCache(): Repository
    {
        return $this->cache;
    }

    /**
     * Check if the cache driver supports tags.
     */
    public function supportsTags(): bool
    {
        try {
            $this->cache->tags(['test']);

            return true;
        } catch (\BadMethodCallException) {
            return false;
        }
    }

    /**
     * Get or set a cached value with optional tags.
     *
     * @param  array<string>  $tags
     */
    public function remember(string $key, array $tags, int $ttl, callable $callback): mixed
    {
        if (! $this->enabled) {
            return $callback();
        }

        $fullKey = $this->prefix.'.'.$key;

        if ($this->supportsTags() && ! empty($tags)) {
            $result = $this->cache->tags($tags)->remember($fullKey, $ttl * 60, $callback);
        } else {
            $result = $this->cache->remember($fullKey, $ttl * 60, $callback);
        }

        return $result;
    }

    /**
     * Invalidate cache by tags.
     *
     * @param  array<string>  $tags
     */
    public function invalidateTags(array $tags): void
    {
        if (! $this->supportsTags()) {
            $this->logWarning('Cache driver does not support tags. Manual cache clearing required.');

            return;
        }

        foreach ($tags as $tag) {
            $this->cache->tags([$tag])->flush();
            $this->logInfo("Cache invalidated for tag: {$tag}");
        }
    }

    /**
     * Invalidate cache for a specific model table.
     */
    public function invalidateModel(string $table): void
    {
        $this->invalidateTags([$this->prefix.'.'.$table]);
    }

    /**
     * Log cache hit.
     */
    public function logHit(string $key): void
    {
        $this->logInfo("Cache HIT: {$key}");
    }

    /**
     * Log cache miss.
     */
    public function logMiss(string $key): void
    {
        $this->logInfo("Cache MISS: {$key}");
    }

    protected function logInfo(string $message): void
    {
        if ($this->logging) {
            Log::info("[SmartCache] {$message}");
        }
    }

    protected function logWarning(string $message): void
    {
        if ($this->logging) {
            Log::warning("[SmartCache] {$message}");
        }
    }
}

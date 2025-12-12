<?php

declare(strict_types=1);

namespace DialloIbrahima\SmartCache;

class SmartCacheStats
{
    protected int $hits = 0;

    protected int $misses = 0;

    /**
     * @var array<int, array{key: string, table: string, type: string, time: string}>
     */
    protected array $queries = [];

    protected int $maxQueries = 100;

    /**
     * Record a cache hit.
     */
    public function recordHit(string $key, string $table, string $type = 'get'): void
    {
        $this->hits++;
        $this->addQuery($key, $table, $type, 'hit');
    }

    /**
     * Record a cache miss.
     */
    public function recordMiss(string $key, string $table, string $type = 'get'): void
    {
        $this->misses++;
        $this->addQuery($key, $table, $type, 'miss');
    }

    /**
     * Add a query to the log.
     */
    protected function addQuery(string $key, string $table, string $type, string $status): void
    {
        $this->queries[] = [
            'key' => $key,
            'table' => $table,
            'type' => $type,
            'status' => $status,
            'time' => now()->format('H:i:s'),
        ];

        // Keep only last N queries
        if (count($this->queries) > $this->maxQueries) {
            $this->queries = array_slice($this->queries, -$this->maxQueries);
        }
    }

    /**
     * Get hit count.
     */
    public function getHits(): int
    {
        return $this->hits;
    }

    /**
     * Get miss count.
     */
    public function getMisses(): int
    {
        return $this->misses;
    }

    /**
     * Get total requests.
     */
    public function getTotal(): int
    {
        return $this->hits + $this->misses;
    }

    /**
     * Get hit ratio as percentage.
     */
    public function getHitRatio(): float
    {
        $total = $this->getTotal();

        if ($total === 0) {
            return 0.0;
        }

        return round(($this->hits / $total) * 100, 2);
    }

    /**
     * Get recent queries.
     *
     * @return array<int, array{key: string, table: string, type: string, status: string, time: string}>
     */
    public function getQueries(): array
    {
        return array_reverse($this->queries);
    }

    /**
     * Reset all stats.
     */
    public function reset(): void
    {
        $this->hits = 0;
        $this->misses = 0;
        $this->queries = [];
    }

    /**
     * Get all stats as array.
     *
     * @return array{hits: int, misses: int, total: int, ratio: float, queries: array}
     */
    public function toArray(): array
    {
        return [
            'hits' => $this->hits,
            'misses' => $this->misses,
            'total' => $this->getTotal(),
            'ratio' => $this->getHitRatio(),
            'queries' => $this->getQueries(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace DialloIbrahima\SmartCache\Support;

use Illuminate\Database\Eloquent\Builder;

class CacheKeyGenerator
{
    /**
     * Generate a unique cache key based on the query.
     */
    public static function generate(Builder $builder): string
    {
        $query = $builder->getQuery();

        // Get the SQL with bindings
        $sql = $query->toSql();
        $bindings = $query->getBindings();

        // Include eager loads in the key
        $eagerLoads = array_keys($builder->getEagerLoads());

        // Build a unique key from all components
        $components = [
            'sql' => $sql,
            'bindings' => $bindings,
            'eager_loads' => $eagerLoads,
        ];

        return md5(serialize($components));
    }

    /**
     * Generate a cache key for count queries.
     */
    public static function generateForCount(Builder $builder): string
    {
        return self::generate($builder).':count';
    }

    /**
     * Generate a cache key for aggregate queries.
     */
    public static function generateForAggregate(Builder $builder, string $function, string $column): string
    {
        return self::generate($builder).':'.$function.':'.$column;
    }

    /**
     * Generate a cache key for a single record.
     */
    public static function generateForFirst(Builder $builder): string
    {
        return self::generate($builder).':first';
    }
}

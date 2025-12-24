<?php

declare(strict_types=1);

namespace DialloIbrahima\SmartCache;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use ReflectionClass;

class SmartCacheDiscovery
{
    /**
     * Discover all models using the HasSmartCache trait.
     *
     * @return array<int, array{class: string, table: string, short_name: string, invalidates: array<string>}>
     */
    public function discoverCachedModels(): array
    {
        $models = [];
        $modelsPath = app_path('Models');

        if (! File::isDirectory($modelsPath)) {
            return $models;
        }

        $files = File::allFiles($modelsPath);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $className = $this->getClassFromFile($file->getPathname());

            if ($className === null) {
                continue;
            }

            if ($this->usesHasSmartCache($className)) {
                try {
                    /** @var Model $instance */
                    $instance = new $className;
                    
                    // Get related models that this model invalidates
                    $invalidates = [];
                    if (method_exists($className, 'invalidatesSmartCacheOf')) {
                        /** @var array<class-string<Model>> $relatedClasses */
                        $relatedClasses = $className::invalidatesSmartCacheOf();
                        foreach ($relatedClasses as $relatedClass) {
                            $invalidates[] = class_basename($relatedClass);
                        }
                    }
                    
                    $models[] = [
                        'class' => $className,
                        'table' => $instance->getTable(),
                        'short_name' => class_basename($className),
                        'invalidates' => $invalidates,
                    ];
                } catch (\Throwable) {
                    // Skip models that can't be instantiated
                    continue;
                }
            }
        }

        // Sort by short name
        usort($models, fn ($a, $b) => $a['short_name'] <=> $b['short_name']);

        return $models;
    }

    /**
     * Generate Mermaid diagram code for cache relations.
     *
     * @param array<int, array{class: string, table: string, short_name: string, invalidates: array<string>}> $models
     */
    public function generateMermaidDiagram(array $models): string
    {
        $lines = ['graph LR'];
        $hasRelations = false;

        foreach ($models as $model) {
            $from = $model['short_name'];
            
            foreach ($model['invalidates'] as $to) {
                $lines[] = "    {$from}[\"📦 {$from}\"] -->|invalidates| {$to}[\"📦 {$to}\"]";
                $hasRelations = true;
            }
        }

        // If no relations, show isolated nodes
        if (! $hasRelations) {
            foreach ($models as $model) {
                $name = $model['short_name'];
                $lines[] = "    {$name}[\"📦 {$name}\"]";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Get all tables that should be invalidated when a specific table is cleared.
     *
     * @param array<int, array{class: string, table: string, short_name: string, invalidates: array<string>}> $models
     * @return array<string> List of related table names
     */
    public function getRelatedTables(string $table, array $models): array
    {
        $relatedTables = [];
        
        // Find the model for this table
        foreach ($models as $model) {
            if ($model['table'] === $table && ! empty($model['invalidates'])) {
                // Find tables for invalidated models
                foreach ($model['invalidates'] as $invalidatedShortName) {
                    foreach ($models as $targetModel) {
                        if ($targetModel['short_name'] === $invalidatedShortName) {
                            $relatedTables[] = $targetModel['table'];
                        }
                    }
                }
            }
        }
        
        return $relatedTables;
    }

    /**
     * Get the fully qualified class name from a PHP file.
     */
    protected function getClassFromFile(string $filePath): ?string
    {
        $contents = File::get($filePath);

        // Extract namespace
        $namespace = null;
        if (preg_match('/namespace\s+([^;]+);/', $contents, $matches)) {
            $namespace = $matches[1];
        }

        // Extract class name
        if (preg_match('/class\s+(\w+)/', $contents, $matches)) {
            $className = $matches[1];

            if ($namespace) {
                return $namespace.'\\'.$className;
            }

            return $className;
        }

        return null;
    }

    /**
     * Check if a class uses the HasSmartCache trait.
     */
    protected function usesHasSmartCache(string $className): bool
    {
        if (! class_exists($className)) {
            return false;
        }

        try {
            $reflection = new ReflectionClass($className);

            // Check if it's a concrete Model class
            if ($reflection->isAbstract() || ! $reflection->isSubclassOf(Model::class)) {
                return false;
            }

            // Get all traits used by this class and its parents using Laravel helper
            /** @var array<class-string, class-string> $traits */
            $traits = class_uses_recursive($className);

            return array_key_exists(HasSmartCache::class, $traits);
        } catch (\Throwable) {
            return false;
        }
    }
}

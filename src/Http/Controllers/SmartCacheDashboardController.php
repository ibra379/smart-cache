<?php

declare(strict_types=1);

namespace DialloIbrahima\SmartCache\Http\Controllers;

use DialloIbrahima\SmartCache\SmartCacheDiscovery;
use DialloIbrahima\SmartCache\SmartCacheManager;
use DialloIbrahima\SmartCache\SmartCacheStats;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class SmartCacheDashboardController extends Controller
{
    public function __construct(
        protected SmartCacheManager $cacheManager,
        protected SmartCacheStats $stats,
        protected SmartCacheDiscovery $discovery
    ) {}

    /**
     * Display the dashboard.
     */
    public function index(): View
    {
        $models = $this->discovery->discoverCachedModels();
        
        return view('smart-cache::dashboard', [
            'stats' => $this->stats->toArray(),
            'enabled' => $this->cacheManager->isEnabled(),
            'supportsTags' => $this->cacheManager->supportsTags(),
            'prefix' => $this->cacheManager->getPrefix(),
            'ttl' => $this->cacheManager->getTtl(),
            'models' => $models,
            'mermaidDiagram' => $this->discovery->generateMermaidDiagram($models),
        ]);
    }

    /**
     * Clear all cache for all discovered models.
     */
    public function clearAll(): RedirectResponse
    {
        $prefix = $this->cacheManager->getPrefix();
        $models = $this->discovery->discoverCachedModels();
        
        // Invalidate cache for each discovered model table
        foreach ($models as $model) {
            $this->cacheManager->invalidateTags([$prefix.'.'.$model['table']]);
        }

        $this->stats->reset();

        /** @var string $dashboardPath */
        $dashboardPath = config('smart-cache.dashboard.path', 'smart-cache');
        $path = '/'.$dashboardPath;

        return redirect($path)
            ->with('success', 'All SmartCache entries cleared ('.count($models).' models).');
    }

    /**
     * Clear cache for a specific table and its related tables.
     */
    public function clearTable(string $table): RedirectResponse
    {
        $prefix = $this->cacheManager->getPrefix();
        $models = $this->discovery->discoverCachedModels();
        
        // Invalidate the main table
        $this->cacheManager->invalidateTags([$prefix.'.'.$table]);
        
        // Invalidate related tables (cascade like Observer does)
        $relatedTables = $this->discovery->getRelatedTables($table, $models);
        foreach ($relatedTables as $relatedTable) {
            $this->cacheManager->invalidateTags([$prefix.'.'.$relatedTable]);
        }

        /** @var string $dashboardPath */
        $dashboardPath = config('smart-cache.dashboard.path', 'smart-cache');
        $path = '/'.$dashboardPath;
        
        $message = "Cache cleared for table: {$table}.";
        if (! empty($relatedTables)) {
            $message .= ' Also cleared: '.implode(', ', $relatedTables).'.';
        }

        return redirect($path)->with('success', $message);
    }
}

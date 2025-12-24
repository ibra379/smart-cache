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
        return view('smart-cache::dashboard', [
            'stats' => $this->stats->toArray(),
            'enabled' => $this->cacheManager->isEnabled(),
            'supportsTags' => $this->cacheManager->supportsTags(),
            'prefix' => $this->cacheManager->getPrefix(),
            'ttl' => $this->cacheManager->getTtl(),
            'models' => $this->discovery->discoverCachedModels(),
        ]);
    }

    /**
     * Clear all cache.
     */
    public function clearAll(): RedirectResponse
    {
        if ($this->cacheManager->supportsTags()) {
            $prefix = $this->cacheManager->getPrefix();
            $this->cacheManager->invalidateTags([$prefix]);
        }

        $this->stats->reset();

        /** @var string $dashboardPath */
        $dashboardPath = config('smart-cache.dashboard.path', 'smart-cache');
        $path = '/'.$dashboardPath;

        return redirect($path)
            ->with('success', 'All SmartCache entries cleared.');
    }

    /**
     * Clear cache for a specific table.
     */
    public function clearTable(string $table): RedirectResponse
    {
        $prefix = $this->cacheManager->getPrefix();
        $this->cacheManager->invalidateTags([$prefix.'.'.$table]);

        /** @var string $dashboardPath */
        $dashboardPath = config('smart-cache.dashboard.path', 'smart-cache');
        $path = '/'.$dashboardPath;

        return redirect($path)
            ->with('success', "Cache cleared for table: {$table}.");
    }
}

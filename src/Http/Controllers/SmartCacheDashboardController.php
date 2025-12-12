<?php

declare(strict_types=1);

namespace DialloIbrahima\SmartCache\Http\Controllers;

use DialloIbrahima\SmartCache\SmartCacheManager;
use DialloIbrahima\SmartCache\SmartCacheStats;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class SmartCacheDashboardController extends Controller
{
    public function __construct(
        protected SmartCacheManager $cacheManager,
        protected SmartCacheStats $stats
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

        $path = '/'.config('smart-cache.dashboard.path', 'smart-cache');

        return redirect($path)
            ->with('success', 'All SmartCache entries cleared.');
    }

    /**
     * Clear cache for a specific model.
     */
    public function clearModel(Request $request): RedirectResponse
    {
        $modelClass = $request->input('model');

        if (! $modelClass || ! class_exists($modelClass)) {
            $path = '/'.config('smart-cache.dashboard.path', 'smart-cache');

            return redirect($path)
                ->with('error', 'Invalid model class.');
        }

        /** @var Model $instance */
        $instance = new $modelClass;
        $table = $instance->getTable();
        $prefix = $this->cacheManager->getPrefix();

        $this->cacheManager->invalidateTags([$prefix.'.'.$table]);

        $path = '/'.config('smart-cache.dashboard.path', 'smart-cache');

        return redirect($path)
            ->with('success', "Cache cleared for {$modelClass}.");
    }
}

# Changelog

All notable changes to SmartCache will be documented in this file.

## [2.2.0] - 2024-12-24

### Added
- **Cache Relations Diagram** - Mermaid diagram in dashboard showing cache invalidation relationships
- **Cascade Invalidation** - `clearTable()` now invalidates related models automatically
- `generateMermaidDiagram()` method in SmartCacheDiscovery
- `getRelatedTables()` method for cascade invalidation lookup
- 4 new tests for relations and diagram functionality

### Fixed
- **Clear All not working** - Now properly invalidates all discovered model tables
- Dashboard shows "Invalidates" column for each model

### Developer Experience
- 63 tests with 124 assertions
- PHPStan level max compliance

## [2.1.0] - 2024-12-24

### Added
- **Model Auto-Discovery** - Dashboard automatically detects all models using `HasSmartCache` trait
- **Quick Invalidation Buttons** - Each model in dashboard has an "Invalidate" button
- `SmartCacheDiscovery` class for scanning and discovering cached models
- 17 new tests for dashboard and discovery functionality

### Changed
- Dashboard now shows a "Cached Models" section instead of "Recent Queries"
- Replaced `clearModel()` with `clearTable()` for simpler cache invalidation
- Removed manual model input field (no longer needed with auto-discovery)

### Developer Experience
- 59 tests with 111 assertions
- PHPStan level max compliance

## [2.0.0] - 2025-12-12

### Added
- **Granular Cache Invalidation** - `smartFind($id)` method for record-level caching
- **Web Dashboard** - Monitor hits/misses, view cached queries, clear cache from UI
- **Artisan Command** - `php artisan smart-cache:clear` for CLI cache management
- **Stats Tracking** - `SmartCacheStats` class tracks hits, misses, and query logs

### Changed
- Dashboard controller uses path-based redirects instead of named routes
- `remember()` method now tracks table and type for stats

### Developer Experience
- 49 tests with 98 assertions
- Comprehensive test coverage for all new features

## [1.1.0] - 2025-12-11

### Added
- New `invalidatesSmartCacheOf()` method for related model cache invalidation
- When a model changes, it can automatically invalidate cache of related models
- 4 new tests for related cache invalidation feature

## [1.0.1] - 2025-12-11

### Fixed
- Added PHPDoc `@method` annotations for better IDE/PHPStan support
- IDEs now correctly recognize return types for `smartGet()`, `smartFirst()`, etc.

## [1.0.0] - 2025-12-10

### Added
- Initial release
- `HasSmartCache` trait for Eloquent models
- Automatic query caching with `smartGet()`, `smartFirst()`, `smartCount()`
- Aggregate caching: `smartSum()`, `smartAvg()`, `smartMax()`, `smartMin()`
- Automatic cache invalidation on model create/update/delete
- Configurable TTL (per-query or global)
- Cache tags support for Redis/Memcached
- Logging support for debugging

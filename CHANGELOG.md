# Changelog

All notable changes to SmartCache will be documented in this file.

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

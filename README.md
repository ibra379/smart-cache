<h1 align="center">🚀 Laravel SmartCache</h1>

<p align="center">
<a href="https://packagist.org/packages/dialloibrahima/smart-cache"><img src="https://img.shields.io/packagist/v/dialloibrahima/smart-cache.svg?style=flat-square" alt="Latest Version on Packagist"></a>
<a href="https://github.com/ibra379/smart-cache/actions?query=workflow%3Arun-tests+branch%3Amain"><img src="https://img.shields.io/github/actions/workflow/status/ibra379/smart-cache/run-tests.yml?branch=main&label=tests&style=flat-square" alt="GitHub Tests Action Status"></a>
<a href="https://packagist.org/packages/dialloibrahima/smart-cache"><img src="https://img.shields.io/packagist/dt/dialloibrahima/smart-cache.svg?style=flat-square" alt="Total Downloads"></a>
<a href="https://www.php.net/"><img src="https://img.shields.io/badge/php-%5E8.2-8892BF.svg?style=flat-square" alt="PHP Version"></a>
<a href="https://laravel.com/"><img src="https://img.shields.io/badge/laravel-10.x%20%7C%2011.x%20%7C%2012.x-FF2D20.svg?style=flat-square" alt="Laravel Version"></a>
</p>

**SmartCache** adds automatic and intelligent caching to Eloquent queries, with automatic invalidation when data changes. **Zero configuration, maximum performance.**

---

## 📋 Table of Contents

- [The Problem](#-the-problem)
- [The Solution](#-the-solution)
- [Installation](#-installation)
- [Quick Start](#-quick-start)
- [How It Works](#-how-it-works)
- [API Reference](#-api-reference)
- [Configuration](#%EF%B8%8F-configuration)
- [Use Cases](#-use-cases)
- [Best Practices](#-best-practices)
- [Testing](#-testing)
- [Requirements](#-requirements)
- [Roadmap](#-roadmap)
- [Comparison with Alternatives](#-comparison-with-alternatives)
- [Contributing](#-contributing)
- [License](#-license)

---

## 😰 The Problem

Manually managing Eloquent query caching is tedious and error-prone:

```php
// ❌ BEFORE: Manual cache everywhere
$users = Cache::remember('active_users', 3600, function () {
    return User::where('active', true)->get();
});

// And remember to invalidate... always!
User::created(function ($user) {
    Cache::forget('active_users');  // 😱 Easy to forget!
});

User::updated(function ($user) {
    Cache::forget('active_users');  // 😱 You have to do this for every event!
});

User::deleted(function ($user) {
    Cache::forget('active_users');  // 😱 And for every query!
});
```

### Common Problems:

| Problem | Impact |
|---------|--------|
| 🔄 Manual cache everywhere | Duplicated and verbose code |
| 🧠 Easy to forget invalidation | Stale (old) data shown to users |
| 🐛 Hardcoded cache keys | Collisions and hard-to-debug bugs |
| 📊 No centralized management | Impossible to monitor cache hits/misses |

---

## ✨ The Solution

With **SmartCache**, everything becomes automatic:

```php
// ✅ AFTER: One line, everything handled automatically
$users = User::smartCache()->where('active', true)->smartGet();

// ✅ Automatically cached with a unique key based on the query
// ✅ Invalidated when User is created/updated/deleted
// ✅ Zero configuration needed
```

---

## 📦 Installation

```bash
composer require dialloibrahima/smart-cache
```

Publish the configuration file (optional):

```bash
php artisan vendor:publish --tag="smart-cache-config"
```

---

## ⚡ Quick Start

### 1. Add the Trait to Your Model

```php
<?php

namespace App\Models;

use DialloIbrahima\SmartCache\HasSmartCache;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasSmartCache;
    
    // Rest of your model...
}
```

### 2. Use Smart Caching in Your Queries

```php
// Retrieve active users with caching
$users = User::smartCache()->where('active', true)->smartGet();

// Next time, data comes from cache! ⚡
```

### 3. Everything Else is Automatic! 🎉

When you create, update, or delete a user, the cache is automatically invalidated.

---

## 🔧 How It Works

### Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                      Eloquent Query                         │
│  User::smartCache()->where('active', true)->smartGet()      │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                   SmartCacheManager                         │
│  1. Generates unique cache key based on:                    │
│     - SQL Query                                             │
│     - Bindings (parameters)                                 │
│     - Eager Loads (relationships)                           │
│  2. Checks if result exists in cache                        │
└──────────────────────────┬──────────────────────────────────┘
                           │
           ┌───────────────┴───────────────┐
           │                               │
           ▼                               ▼
    ┌─────────────┐                ┌──────────────┐
    │ Cache HIT   │                │ Cache MISS   │
    │ ⚡ Instant  │                │ Execute SQL  │
    │ return data │                │ Store result │
    └─────────────┘                │ Return data  │
                                   └──────────────┘
```

### Automatic Invalidation

```
┌─────────────────────────────────────────────────────────────┐
│         Model Event (created/updated/deleted)               │
│                    User::create([...])                      │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                  SmartCacheObserver                         │
│  1. Intercepts the event automatically                      │
│  2. Identifies the cache tag for this Model                 │
│  3. Invalidates (flushes) all related caches                │
└─────────────────────────────────────────────────────────────┘
                           │
                           ▼
              ✅ Next query = fresh data!
```

### Cache Key Generation

Each query generates a **unique and deterministic** cache key:

```php
// These two queries have DIFFERENT keys (different bindings)
User::smartCache()->where('role', 'admin')->smartGet();  // key: abc123...
User::smartCache()->where('role', 'user')->smartGet();   // key: def456...

// These two queries have the SAME key
User::smartCache()->where('active', true)->smartGet();   // key: xyz789...
User::smartCache()->where('active', true)->smartGet();   // key: xyz789... ✅ Cache HIT!
```

### Normal Queries (No Breaking Changes)

**SmartCache is opt-in only.** Your existing Eloquent queries continue to work exactly as before - no caching, no changes:

```php
// ❌ NORMAL QUERIES - No caching (standard Laravel behavior)
$users = User::where('active', true)->get();    // Always executes SQL
$user = User::find(1);                           // Always executes SQL
$count = User::count();                          // Always executes SQL
$first = User::where('role', 'admin')->first(); // Always executes SQL

// ✅ SMART CACHE QUERIES - Automatic caching
$users = User::smartCache()->where('active', true)->smartGet();  // Cached!
$user = User::smartCache()->smartFirst();                         // Cached!
$count = User::smartCache()->smartCount();                        // Cached!
```

This design is **intentional**:
- ✅ **No breaking changes** - Existing code works without modification
- ✅ **Explicit caching** - You choose exactly which queries to cache
- ✅ **Safe by default** - Avoids caching queries that shouldn't be cached

> **Note:** The observer is always registered when using the trait, so cache invalidation happens automatically on model events. However, this has no effect on normal queries - it only affects SmartCache queries.

---

## 📖 API Reference

### Available Methods

#### Query Methods

| Method | Description | Returns |
|--------|-------------|---------|
| `smartGet($columns = ['*'])` | Retrieve collection with cache | `Collection` |
| `smartFirst($columns = ['*'])` | Retrieve first result with cache | `Model\|null` |
| `smartCount($column = '*')` | Count results with cache | `int` |
| `smartSum($column)` | Sum values with cache | `float\|int` |
| `smartAvg($column)` | Average values with cache | `float\|int\|null` |
| `smartMax($column)` | Maximum value with cache | `mixed` |
| `smartMin($column)` | Minimum value with cache | `mixed` |

#### Scope Methods

| Method | Description |
|--------|-------------|
| `smartCache(?int $ttl = null)` | Enable caching for the query (optional TTL in minutes) |
| `withoutSmartCache()` | Disable caching for this query |

#### Static Methods

| Method | Description |
|--------|-------------|
| `Model::disableSmartCache()` | Disable caching for the entire model |
| `Model::enableSmartCache()` | Re-enable caching for the model |
| `Model::clearSmartCache()` | Clear all cache for the model |
| `Model::invalidatesSmartCacheOf()` | Define related models to invalidate (override in model) |

### Artisan Commands

Clear cache from the command line:

```bash
# Clear cache for a specific model
php artisan smart-cache:clear App\\Models\\User

# Clear all SmartCache entries
php artisan smart-cache:clear --all
```

### Granular Cache Invalidation (smartFind)

Use `smartFind()` for record-level caching. When a specific record is updated, only its cache is invalidated:

```php
// Cache individual records with record-level tags
$user1 = User::smartCache()->smartFind(1);  // Tag: smart_cache.users.1
$user2 = User::smartCache()->smartFind(2);  // Tag: smart_cache.users.2

// When User 1 is updated, only User 1's cache is invalidated
$user1->update(['name' => 'New Name']);  // Invalidates: smart_cache.users.1
// User 2's cache remains valid!
```

### Related Cache Invalidation

When a related model changes, you might want to invalidate the cache of parent models automatically. Override `invalidatesSmartCacheOf()` in your model:

```php
class Comment extends Model
{
    use HasSmartCache;

    /**
     * When a Comment changes, also invalidate Post and Notification cache.
     */
    public static function invalidatesSmartCacheOf(): array
    {
        return [Post::class, Notification::class];
    }
}
```

Now when you create, update, or delete a `Comment`, the cache for both `Post` and `Notification` will be automatically invalidated!

### Complete Examples

```php
use App\Models\Post;
use App\Models\User;

// ═══════════════════════════════════════════════════════════
// BASIC QUERIES
// ═══════════════════════════════════════════════════════════

// Cache with default TTL (60 minutes)
$posts = Post::smartCache()
    ->where('published', true)
    ->smartGet();

// Cache with custom TTL (30 minutes)
$users = User::smartCache(30)
    ->where('role', 'admin')
    ->smartGet();

// Infinite cache (invalidated only by model events)
$categories = Category::smartCache(0)->smartGet();

// ═══════════════════════════════════════════════════════════
// AGGREGATES
// ═══════════════════════════════════════════════════════════

// Count with cache
$activeCount = User::smartCache()
    ->where('active', true)
    ->smartCount();

// Sum, average, min, max
$totalRevenue = Order::smartCache()->smartSum('amount');
$avgOrderValue = Order::smartCache()->smartAvg('amount');
$highestOrder = Order::smartCache()->smartMax('amount');
$lowestOrder = Order::smartCache()->smartMin('amount');

// ═══════════════════════════════════════════════════════════
// COMPLEX QUERIES
// ═══════════════════════════════════════════════════════════

// With relationships (eager loading)
$posts = Post::smartCache()
    ->with(['author', 'comments.user'])
    ->where('published', true)
    ->orderBy('created_at', 'desc')
    ->take(10)
    ->smartGet();

// With multiple conditions
$users = User::smartCache()
    ->where('active', true)
    ->where('email_verified_at', '!=', null)
    ->whereHas('posts', function ($query) {
        $query->where('published', true);
    })
    ->smartGet();

// ═══════════════════════════════════════════════════════════
// CACHE CONTROL
// ═══════════════════════════════════════════════════════════

// Disable cache for a specific query
$freshUser = User::withoutSmartCache()->find($id);

// Disable cache for entire model
User::disableSmartCache();
$users = User::smartCache()->smartGet(); // Does not use cache
User::enableSmartCache();

// Manually clear cache
User::clearSmartCache(); // Invalidates all User caches
```

---

## ⚙️ Configuration

After publishing the config, you'll find `config/smart-cache.php`:

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cache Driver
    |--------------------------------------------------------------------------
    |
    | The cache driver to use. Set to 'auto' to use Laravel's default driver.
    |
    | Supported: 'auto', 'redis', 'memcached', 'database', 'array'
    |
    | ⚠️  NOTE: 'file' does NOT support cache tags and won't work correctly!
    |
    */
    'driver' => env('SMART_CACHE_DRIVER', 'auto'),

    /*
    |--------------------------------------------------------------------------
    | Default TTL (Time To Live)
    |--------------------------------------------------------------------------
    |
    | Default cache duration in MINUTES.
    | Set to 0 for infinite cache (invalidated only by model events).
    |
    */
    'ttl' => env('SMART_CACHE_TTL', 60),

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | Prefix for all SmartCache keys, to avoid collisions with other
    | cached data in your application.
    |
    */
    'prefix' => 'smart_cache',

    /*
    |--------------------------------------------------------------------------
    | Enable SmartCache
    |--------------------------------------------------------------------------
    |
    | Enable or disable SmartCache globally.
    | Useful for disabling in testing environments.
    |
    */
    'enabled' => env('SMART_CACHE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Enable logging of cache hits and misses for debugging.
    | Logs will appear in Laravel's default log channel.
    |
    */
    'logging' => env('SMART_CACHE_LOGGING', false),
];
```

### Environment Variables

Add to your `.env`:

```env
# Use Redis for best performance
SMART_CACHE_DRIVER=redis

# Cache for 2 hours (120 minutes)
SMART_CACHE_TTL=120

# Enable logging for debugging
SMART_CACHE_LOGGING=true

# Disable in testing
SMART_CACHE_ENABLED=true
```

---

## 📚 Use Cases

### 1. Homepage with Recent Posts

```php
// Controller
public function index()
{
    // Cache for 5 minutes - frequently changing content
    $recentPosts = Post::smartCache(5)
        ->with('author:id,name,avatar')
        ->where('published', true)
        ->latest()
        ->take(10)
        ->smartGet();

    // Cache for 1 hour - rarely changes
    $categories = Category::smartCache(60)
        ->withCount('posts')
        ->orderBy('name')
        ->smartGet();

    // Cache for 24 hours - almost static
    $stats = [
        'total_posts' => Post::smartCache(1440)->smartCount(),
        'total_users' => User::smartCache(1440)->smartCount(),
    ];

    return view('home', compact('recentPosts', 'categories', 'stats'));
}
```

### 2. Admin Dashboard

```php
public function dashboard()
{
    // Aggregated statistics - short cache for always up-to-date data
    return [
        'orders_today' => Order::smartCache(5)
            ->whereDate('created_at', today())
            ->smartCount(),
            
        'revenue_today' => Order::smartCache(5)
            ->whereDate('created_at', today())
            ->smartSum('total'),
            
        'new_users_week' => User::smartCache(15)
            ->where('created_at', '>=', now()->subWeek())
            ->smartCount(),
            
        'avg_order_value' => Order::smartCache(30)
            ->where('status', 'completed')
            ->smartAvg('total'),
    ];
}
```

### 3. API with Rate Limiting

```php
// Perfect for APIs - avoids duplicate queries from repeated calls
public function show(string $slug)
{
    $post = Post::smartCache()
        ->with(['author', 'tags', 'comments.user'])
        ->where('slug', $slug)
        ->where('published', true)
        ->smartFirst();

    if (!$post) {
        abort(404);
    }

    return new PostResource($post);
}
```

### 4. Sidebar with Dynamic Data

```php
// View Composer - executed on every page
View::composer('partials.sidebar', function ($view) {
    $view->with([
        'popularPosts' => Post::smartCache(30)
            ->orderByDesc('views')
            ->take(5)
            ->smartGet(),
            
        'recentComments' => Comment::smartCache(10)
            ->with('user:id,name')
            ->latest()
            ->take(5)
            ->smartGet(),
    ]);
});
```

---

## ✅ Best Practices

### ✅ DO - What to Do

```php
// ✅ Queries that rarely change
$categories = Category::smartCache(120)->smartGet();

// ✅ Aggregates on large datasets
$totalSales = Order::smartCache()->smartSum('amount');

// ✅ Public data shared between users
$featuredProducts = Product::smartCache()
    ->where('featured', true)
    ->smartGet();

// ✅ Short TTL for frequently changing data
$latestNews = News::smartCache(5)->latest()->take(5)->smartGet();

// ✅ Eager loaded relationships
$posts = Post::smartCache()
    ->with('author', 'tags')  // Included in cache key
    ->smartGet();
```

### ❌ DON'T - What to Avoid

```php
// ❌ User-specific queries - each user = new cache entry
$myPosts = Post::smartCache()
    ->where('user_id', auth()->id())  // Better without cache
    ->smartGet();

// ❌ Queries with highly variable parameters
$searchResults = Post::smartCache()
    ->where('title', 'like', "%{$searchTerm}%")  // Too many variations
    ->smartGet();

// ❌ Queries on real-time data
$liveData = Metric::smartCache()  // Don't use cache for real-time
    ->where('timestamp', '>=', now()->subMinute())
    ->smartGet();

// ❌ TTL too long for changing data
$orders = Order::smartCache(1440)->smartGet();  // 24h is too much!
```

### 💡 Recommended Patterns

```php
// Pattern: Conditional caching
$posts = $request->has('search')
    ? Post::withoutSmartCache()->where('title', 'like', "%{$search}%")->get()
    : Post::smartCache()->where('published', true)->smartGet();

// Pattern: Cache by role
$users = auth()->user()->isAdmin()
    ? User::withoutSmartCache()->get()  // Admin always sees fresh data
    : User::smartCache()->where('public', true)->smartGet();

// Pattern: Manual invalidation on specific actions
public function importProducts(Request $request)
{
    // Bulk import...
    Product::clearSmartCache();  // Force refresh after import
}
```

---

## 🧪 Testing

### Disable Cache in Tests

```php
// tests/TestCase.php
protected function setUp(): void
{
    parent::setUp();
    
    // Disable SmartCache in tests
    config(['smart-cache.enabled' => false]);
}
```

### Testing with Active Cache

```php
use DialloIbrahima\SmartCache\SmartCacheManager;

it('caches query results', function () {
    $post = Post::factory()->create(['published' => true]);
    
    // First call - cache miss
    $result1 = Post::smartCache()->where('published', true)->smartGet();
    
    // Second call - cache hit
    $result2 = Post::smartCache()->where('published', true)->smartGet();
    
    expect($result1)->toHaveCount(1);
    expect($result2)->toHaveCount(1);
});

it('invalidates cache on model update', function () {
    $post = Post::factory()->create(['title' => 'Original']);
    
    // Cache the query
    Post::smartCache()->smartGet();
    
    // Update the post - cache invalidated automatically
    $post->update(['title' => 'Updated']);
    
    // Next query reflects the change
    $posts = Post::smartCache()->smartGet();
    expect($posts->first()->title)->toBe('Updated');
});
```

---

## 📋 Requirements

| Requirement | Version |
|-------------|---------|
| PHP | ^8.2 |
| Laravel | 10.x, 11.x, 12.x |
| Cache Driver | Redis (recommended), Memcached, Database, Array |

> ⚠️ **Important**: The `file` driver **does not support cache tags** and won't work correctly with SmartCache. Use Redis for best performance.

### Configuring Redis

```bash
# Install predis
composer require predis/predis

# Or use phpredis (PHP extension)
```

```env
# .env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

---

## 🗺️ Roadmap

### v1.0 ✅ (Current)
- [x] `HasSmartCache` trait for models
- [x] Automatic query caching (get, first, count, sum, avg, min, max)
- [x] Automatic invalidation on model events
- [x] Global and per-query TTL configuration
- [x] Cache tags support
- [x] Cache hits/misses logging
- [x] Complete test suite

### v1.1 (Next)
- [ ] `php artisan smart-cache:clear` - Command to clear cache
- [ ] `php artisan smart-cache:stats` - Usage statistics
- [ ] Cache warming (pre-populate cache)
- [ ] Events for cache hit/miss

### v2.0 (Future)
- [ ] Granular invalidation (per record, not per table)
- [ ] Cache relationships separately
- [ ] Web dashboard for monitoring
- [ ] Multi-server distributed cache support

---

## ⚖️ Comparison with Alternatives

| Feature | SmartCache | Cache::remember() | laravel-responsecache |
|---------|------------|-------------------|----------------------|
| Zero boilerplate | ✅ | ❌ | ✅ |
| Automatic invalidation | ✅ | ❌ | ✅ |
| Query-level cache | ✅ | ✅ | ❌ (response) |
| Aggregates (count, sum...) | ✅ | ❌ | ❌ |
| Per-query TTL | ✅ | ✅ | ❌ |
| Works with JSON APIs | ✅ | ✅ | ⚠️ |
| Tag-based invalidation | ✅ | ❌ | ✅ |

---

## 🤝 Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a branch for your feature (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development

```bash
# Install dependencies
composer install

# Run tests
composer test

# Static analysis
composer analyse

# Code formatting
composer format
```

---

## 📄 License

The MIT License (MIT). See [License File](LICENSE.md) for more information.

---

## 👨‍💻 Credits

- [Ibrahima Diallo](https://github.com/ibra379)

---

<p align="center">
  <strong>⭐ If this package is useful to you, leave a star on GitHub! ⭐</strong>
</p>

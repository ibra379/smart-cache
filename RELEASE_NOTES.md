# 🚀 SmartCache v1.0.0 - Initial Release

Automatic and intelligent Eloquent query caching with automatic invalidation.

## ✨ Features

- **`HasSmartCache` trait** - Add to any Eloquent model for smart caching
- **Automatic caching** - `smartGet()`, `smartFirst()`, `smartCount()`, `smartSum()`, `smartAvg()`, `smartMin()`, `smartMax()`
- **Automatic invalidation** - Cache cleared on model create/update/delete events
- **Configurable TTL** - Global or per-query: `User::smartCache(30)->smartGet()`
- **Cache tags support** - Works with Redis, Memcached, Database drivers
- **Zero configuration** - Works out of the box

## 📦 Installation

```bash
composer require dialloibrahima/smart-cache
```

## ⚡ Quick Start

```php
use DialloIbrahima\SmartCache\HasSmartCache;

class User extends Model
{
    use HasSmartCache;
}

// Cached automatically, invalidated on changes
$users = User::smartCache()->where('active', true)->smartGet();
```

## 📋 Requirements

- PHP 8.2+
- Laravel 10.x / 11.x / 12.x
- Cache driver with tag support (Redis recommended)

## 📖 Documentation

See [README.md](https://github.com/ibra379/smart-cache#readme) for full documentation.

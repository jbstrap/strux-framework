---
title: Cache
slug: /cache
description: High-performance PSR-16 compliant caching with multiple drivers.
---

# Cache

The Cache component provides a **PSR-16 (Simple Cache)** compliant caching system for Strux.
It supports multiple cache drivers and allows you to easily store and retrieve frequently accessed or
expensive-to-compute data.

Caching is optional but strongly recommended for improving performance and reducing database and I/O load.

---

## Overview

The cache system consists of:

- A **Cache Manager** (`Strux\Component\Cache\Cache`)
- Multiple **Cache Drivers**
- Centralized **Configuration**

All cache operations conform to the PSR-16 interface.

---

## Architecture

```text
Application
   |
   v
Cache (Manager)
   |
   +-- Default Store
   |
   +-- FilesystemCache
   +-- ApcuCache
   +-- ArrayCache
````

The Cache manager resolves the configured store and delegates all operations to the active driver.

---

## Configuration

Cache configuration is defined in `App\Config\Cache`.

### Default Cache Store

```php
'default' => env('CACHE_DRIVER', 'filesystem'),
```

This determines which cache store is used when no store is explicitly specified.

Supported drivers:

* `filesystem`
* `array`
* `apcu`

---

### Cache Stores

Multiple cache stores may be defined, even using the same driver.

#### Filesystem Store

```php
'filesystem' => [
    'driver' => 'filesystem',
    'path' => ROOT_PATH . '/var/cache/simple',
    'salt' => 'YOUR_APP_SPECIFIC_SALT_FILESYSTEM',
],
```

* Stores cache entries as files
* Uses hashed filenames
* Works in all environments
* Slower than memory-based caches

---

#### APCu Store

```php
'apcu' => [
    'driver' => 'apcu',
    'prefix' => 'app_cache_',
    'salt' => 'YOUR_APP_SPECIFIC_SALT_APCU',
],
```

* In-memory cache
* Extremely fast
* Requires the APCu PHP extension
* Cache is shared per PHP process pool

:::warning CLI Usage
If using APCu in CLI commands or queue workers, ensure:

```ini
apc.enable_cli = 1
```

:::

---

#### Array Store

```php
'array' => [
    'driver' => 'array',
    'salt' => 'YOUR_APP_SPECIFIC_SALT_ARRAY',
],
```

* Stored only in memory
* Cleared on every request
* Intended for testing and development

---

## Environment Variables

Example `.env` configuration:

```env
CACHE_DRIVER=apcu
CACHE_APCU_PREFIX=app_cache_
CACHE_APCU_SALT=random-secret-string
```

---

## Using the Cache

### Resolving the Cache

```php
use Strux\Component\Cache\Cache;

$cache = container(Cache::class);
```

---

### Basic Operations

```php
$cache->set('users.count', 150, 300);
$count = $cache->get('users.count', 0);
```

---

### Using DateInterval TTL

```php
use DateInterval;

$cache->set('report.data', $data, new DateInterval('PT1H'));
```

---

### Checking for Existence

```php
if ($cache->has('settings')) {
    // Cache entry exists and is not expired
}
```

---

### Deleting Cache Entries

```php
$cache->delete('users.count');
$cache->clear();
```

---

### Working with Multiple Keys

```php
$cache->setMultiple([
    'a' => 1,
    'b' => 2,
], 60);

$values = $cache->getMultiple(['a', 'b'], null);
```

---

## Multiple Cache Stores

You may explicitly choose a cache store at runtime:

```php
$cache->store('filesystem')->set('key', 'value');
$cache->store('apcu')->get('key');
```

This is useful for:

* Separating short-lived and persistent cache
* Avoiding APCu pollution
* Testing alternative drivers

---

## Cache Keys (PSR-16)

Cache keys must:

* Be non-empty strings
* Not contain the characters: `{ } ( ) / \ @ :`

Invalid keys will throw a `CacheException`.

---

## Driver Behavior

### APCu Cache

* Keys are transformed internally using:

```text
prefix + sha1(key + salt)
```

* TTL is handled natively by APCu
* `clear()` clears the entire APCu user cache

---

### Filesystem Cache

* Each entry is stored as a `.cache` file
* Data is stored as JSON:

```json
{
  "value": "...",
  "ttl": 1700000000
}
```

* Uses atomic writes (temporary file + rename)
* Expired entries are removed on access

---

### Array Cache

* Stored in PHP arrays
* TTL handled manually
* Never shared across requests
* Fast but volatile

---

## Error Handling & Logging

* All drivers accept an optional `LoggerInterface`
* Cache hits, misses, and failures can be logged
* Misconfiguration or invalid usage throws `CacheException`

---

## Common Usage Patterns

### Caching Database Queries

```php
$users = $cache->get('users.all');

if ($users === null) {
    $users = $userRepository->findAll();
    $cache->set('users.all', $users, 600);
}
```

---

### Caching Expensive Computations

```php
$result = $cache->get('stats.daily');

if ($result === null) {
    $result = $statsService->calculate();
    $cache->set('stats.daily', $result, 3600);
}
```

---

## Best Practices

### Choose the Right Driver

| Environment | Recommended Driver |
|-------------|--------------------|
| Production  | APCu               |
| Development | Filesystem         |
| Testing     | Array              |

---

### Use Clear, Namespaced Keys

```text
user:42:profile
settings:global
report:2026:01
```

Avoid generic keys such as `data` or `cache`.

---

### Always Set a TTL

Avoid infinite cache unless the data is truly static.

```php
$cache->set('config', $config, 3600);
```

---

### Cache Identifiers, Not Heavy Objects

Prefer caching IDs or simple arrays rather than large object graphs.

---

### Assume Cache Is Volatile

Cached data may:

* Expire
* Be evicted
* Be cleared manually

Your application must continue to work without cache.

---

### Avoid Clearing Shared Caches

Calling `clear()` removes all entries in the store.
Prefer targeted invalidation using specific keys.

---

## Summary

The Strux Cache system provides:

* PSR-16 compliance
* Multiple interchangeable drivers
* Strong configuration support
* Predictable behavior across environments

Used correctly, caching significantly improves performance while keeping application logic clean and maintainable.
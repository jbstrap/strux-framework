<?php

declare(strict_types=1);

namespace Strux\Support\Bridge;

use DateInterval;
use Psr\SimpleCache\CacheInterface;
use Strux\Support\FrameworkBridge;

/**
 * @method static CacheInterface store(?string $name = null)
 * @method static mixed get(string $key, mixed $default = null)
 * @method static bool set(string $key, mixed $value, null|int|DateInterval $ttl = null)
 * @method static bool delete(string $key)
 * @method static bool clear()
 * @method static iterable getMultiple(iterable $keys, mixed $default = null)
 * @method static bool setMultiple(iterable $values, null|int|DateInterval $ttl = null)
 * @method static bool deleteMultiple(iterable $keys)
 * @method static bool has(string $key)
 * @see \Strux\Component\Cache\Cache
 */
class Cache extends FrameworkBridge
{
    /**
     * @inheritDoc
     */
    protected static function getAccessor(): string
    {
        return CacheInterface::class;
    }
}
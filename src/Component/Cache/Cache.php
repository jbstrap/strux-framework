<?php

declare(strict_types=1);

namespace Strux\Component\Cache;

use DateInterval;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Strux\Component\Cache\Drivers\ApcuCache;
use Strux\Component\Cache\Drivers\ArrayCache;
use Strux\Component\Cache\Drivers\FilesystemCache;
use Strux\Component\Cache\Events\CacheHit;
use Strux\Component\Cache\Events\CacheMiss;
use Strux\Component\Cache\Events\KeyForgotten;
use Strux\Component\Cache\Events\KeyWritten;
use Strux\Component\Config\Config;
use Strux\Component\Exceptions\CacheException;

class Cache implements CacheInterface
{
    protected CacheInterface $driver;
    protected ?LoggerInterface $logger;
    protected ?EventDispatcherInterface $events;
    protected Config $config;
    protected string $defaultStoreName;

    public function __construct(
        Config                    $config,
        ?LoggerInterface          $logger = null,
        ?EventDispatcherInterface $events = null
    )
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->events = $events;

        $this->defaultStoreName = $this->config->get('cache.default', 'filesystem');
        $this->driver = $this->resolveStore($this->defaultStoreName);
    }

    public function store(?string $name = null): CacheInterface
    {
        if ($name === null || $name === $this->defaultStoreName) {
            return $this->driver;
        }
        return $this->resolveStore($name);
    }

    protected function resolveStore(string $name): CacheInterface
    {
        $storeConfig = $this->config->get("cache.stores.$name");

        if (empty($storeConfig)) {
            throw new CacheException("Cache store configuration '$name' not found.");
        }

        $driverName = $storeConfig['driver'] ?? null;
        $driverConfig = $storeConfig;

        return match ($driverName) {
            'filesystem' => new FilesystemCache($driverConfig, $this->logger),
            'array' => new ArrayCache($driverConfig, $this->logger),
            'apcu' => new ApcuCache($driverConfig, $this->logger),
            default => throw new CacheException("Unsupported cache driver '$driverName' for store '$name'."),
        };
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->driver->get($key, $default);

        if ($value === $default) {
            $this->events?->dispatch(new CacheMiss($key));
        } else {
            $this->events?->dispatch(new CacheHit($key, $value));
        }

        return $value;
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $result = $this->driver->set($key, $value, $ttl);

        if ($result) {
            $this->events?->dispatch(new KeyWritten($key, $value, $ttl));
        }

        return $result;
    }

    public function delete(string $key): bool
    {
        $result = $this->driver->delete($key);

        if ($result) {
            $this->events?->dispatch(new KeyForgotten($key));
        }

        return $result;
    }

    public function clear(): bool
    {
        return $this->driver->clear();
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $values = $this->driver->getMultiple($keys, $default);

        if ($values instanceof \Traversable) {
            $values = iterator_to_array($values);
        }

        if ($this->events) {
            foreach ($values as $key => $value) {
                if ($value === $default) {
                    $this->events->dispatch(new CacheMiss((string)$key));
                } else {
                    $this->events->dispatch(new CacheHit((string)$key, $value));
                }
            }
        }

        return $values;
    }

    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        $result = $this->driver->setMultiple($values, $ttl);

        if ($result && $this->events) {
            foreach ($values as $key => $value) {
                $this->events->dispatch(new KeyWritten((string)$key, $value, $ttl));
            }
        }

        return $result;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $result = $this->driver->deleteMultiple($keys);

        if ($result && $this->events) {
            foreach ($keys as $key) {
                $this->events->dispatch(new KeyForgotten((string)$key));
            }
        }

        return $result;
    }

    public function has(string $key): bool
    {
        return $this->driver->has($key);
    }
}
<?php

declare(strict_types=1);

namespace Strux\Component\Cache;

use DateInterval;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Strux\Component\Cache\Drivers\ApcuCache;
use Strux\Component\Cache\Drivers\ArrayCache;
use Strux\Component\Cache\Drivers\FilesystemCache;
use Strux\Component\Config\Config;
use Strux\Component\Exceptions\CacheException;

class Cache implements CacheInterface
{
    protected CacheInterface $driver;
    protected ?LoggerInterface $logger;
    protected Config $configService;
    protected string $defaultStoreName;

    public function __construct(Config $configService, ?LoggerInterface $logger = null)
    {
        $this->configService = $configService;
        $this->logger = $logger;

        $this->defaultStoreName = $this->configService->get('cache.default', 'filesystem');
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
        $storeConfig = $this->configService->get("cache.stores.$name");

        if (empty($storeConfig)) {
            throw new CacheException("Cache store configuration '$name' not found.");
        }

        $driverName = $storeConfig['driver'] ?? null;
        $driverConfig = $storeConfig; // Pass the whole store etc to the driver

        return match ($driverName) {
            'filesystem' => new FilesystemCache($driverConfig, $this->logger),
            'array' => new ArrayCache($driverConfig, $this->logger),
            'apcu' => new ApcuCache($driverConfig, $this->logger),
            default => throw new CacheException("Unsupported cache driver '$driverName' for store '$name'."),
        };
    }

    // PSR-16 methods delegated to $this->driver
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->driver->get($key, $default);
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        return $this->driver->set($key, $value, $ttl);
    }

    public function delete(string $key): bool
    {
        return $this->driver->delete($key);
    }

    public function clear(): bool
    {
        return $this->driver->clear();
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        return $this->driver->getMultiple($keys, $default);
    }

    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        return $this->driver->setMultiple($values, $ttl);
    }

    public function deleteMultiple(iterable $keys): bool
    {
        return $this->driver->deleteMultiple($keys);
    }

    public function has(string $key): bool
    {
        return $this->driver->has($key);
    }
}
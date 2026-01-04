<?php

declare(strict_types=1);

namespace Strux\Component\Cache\Drivers;

use DateInterval;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Strux\Component\Exceptions\CacheException;

class ArrayCache implements CacheInterface
{
    protected array $storage = [];
    protected array $expirations = []; // Store expiration timestamps
    protected string $salt; // Less critical for an array but for interface consistency
    protected ?LoggerInterface $logger;

    public function __construct(array $config, ?LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        $this->salt = $config['salt'] ?? 'default_array_salt';
    }

    protected function validateKey(string $key): void
    {
        if ($key === '') throw new CacheException('Cache key cannot be empty.');
        if (preg_match('/[{}()\/\\\\@:]/', $key)) {
            throw new CacheException("Invalid character in cache key: $key.");
        }
    }

    // Key with salt for theoretical namespacing if this array was ever shared (unlikely)
    protected function getInternalKey(string $key): string
    {
        return $this->salt . '_' . $key;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->validateKey($key);
        $internalKey = $this->getInternalKey($key);

        if (!array_key_exists($internalKey, $this->storage)) {
            $this->logger?->debug("[Array Cache] MISS for key '$key'.");
            return $default;
        }

        if (isset($this->expirations[$internalKey]) && time() > $this->expirations[$internalKey]) {
            $this->logger?->debug("[Array Cache] EXPIRED for key '$key'.");
            $this->delete($key); // Uses web delete which handles both arrays
            return $default;
        }
        $this->logger?->debug("[Array Cache] HIT for key '$key'.");
        return $this->storage[$internalKey];
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->validateKey($key);
        $internalKey = $this->getInternalKey($key);

        if ($ttl instanceof DateInterval) {
            $expiresAt = (new DateTimeImmutable())->add($ttl)->getTimestamp();
        } elseif (is_int($ttl)) {
            if ($ttl <= 0) return $this->delete($key);
            $expiresAt = time() + $ttl;
        } else if ($ttl === null) {
            $expiresAt = null;
        } else {
            throw new CacheException('Invalid TTL type.');
        }

        $this->storage[$internalKey] = $value;
        if ($expiresAt !== null) {
            $this->expirations[$internalKey] = $expiresAt;
        } else {
            unset($this->expirations[$internalKey]); // No expiration
        }
        $this->logger?->info("[Array Cache] SET for key '$key'.");
        return true;
    }

    public function delete(string $key): bool
    {
        $this->validateKey($key);
        $internalKey = $this->getInternalKey($key);
        unset($this->storage[$internalKey], $this->expirations[$internalKey]);
        $this->logger?->info("[Array Cache] DELETED for key '$key'.");
        return true;
    }

    public function clear(): bool
    {
        $this->storage = [];
        $this->expirations = [];
        $this->logger?->info("[Array Cache] CLEARED.");
        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $results = [];
        foreach ($keys as $key) {
            $sKey = (string)$key;
            $this->validateKey($sKey);
            $results[$sKey] = $this->get($sKey, $default);
        }
        return $results;
    }

    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        $success = true;
        foreach ($values as $key => $value) {
            if (!$this->set((string)$key, $value, $ttl)) $success = false;
        }
        return $success;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $success = true;
        foreach ($keys as $key) {
            if (!$this->delete((string)$key)) $success = false;
        }
        return $success;
    }

    public function has(string $key): bool
    {
        $this->validateKey($key);
        $internalKey = $this->getInternalKey($key);
        if (!array_key_exists($internalKey, $this->storage)) {
            return false;
        }
        if (isset($this->expirations[$internalKey]) && time() > $this->expirations[$internalKey]) {
            return false; // Expired, effectively doesn't "have" it for get
        }
        return true;
    }
}
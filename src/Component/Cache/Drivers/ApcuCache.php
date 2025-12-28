<?php

declare(strict_types=1);

namespace Strux\Component\Cache\Drivers;

use DateInterval;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Strux\Component\Exceptions\CacheException;

class ApcuCache implements CacheInterface
{
    protected string $prefix;
    protected string $salt;
    protected ?LoggerInterface $logger;

    public function __construct(array $config, ?LoggerInterface $logger = null)
    {
        $apcuAvailable = function_exists('apcu_enabled') && apcu_enabled();

        if (!$apcuAvailable || !extension_loaded('apcu') || !ini_get('apc.enabled')) {
            throw new CacheException('APCu extension is not loaded or not enabled.');
        }
        // Note: For CLI, apc.enable_cli must be On in php.ini.

        $this->logger = $logger;
        $this->prefix = $config['prefix'] ?? 'apcu_default_';
        $this->salt = $config['salt'] ?? 'default_apcu_salt';
    }

    protected function validateKey(string $key): void
    {
        if ($key === '') {
            throw new CacheException('Cache key cannot be empty.');
        }
        // PSR-16 reserved characters: {}()/\@:
        if (preg_match('/[{}()\/\\\\@:]/', $key)) {
            throw new CacheException("Invalid character in cache key: $key. PSR-16 disallows { } ( ) / \\ @ :");
        }
    }

    protected function getInternalKey(string $key): string
    {
        // Using a prefix and a hash of the salted key for APCu.
        // APCu keys are strings and are global to the PHP process.
        return $this->prefix . sha1($key . $this->salt);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->validateKey($key);
        $internalKey = $this->getInternalKey($key);
        $success = false;
        $value = apcu_fetch($internalKey, $success);

        if (!$success) {
            $this->logger?->debug("[APCu Cache] MISS for key '$key' (internal: $internalKey).");
            return $default;
        }
        $this->logger?->debug("[APCu Cache] HIT for key '$key' (internal: $internalKey).");
        return $value; // APCu handles expiration internally
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->validateKey($key);
        $internalKey = $this->getInternalKey($key);

        $durationSeconds = 0; // Default: 0 means cache "forever" (until cleared or PHP process restarts)
        if ($ttl instanceof DateInterval) {
            $now = new DateTimeImmutable();
            $expires = $now->add($ttl);
            $durationSeconds = max(0, $expires->getTimestamp() - $now->getTimestamp());
        } elseif (is_int($ttl)) {
            if ($ttl <= 0) { // PSR-16: Items with zero or negative TTL should be deleted if they exist.
                return $this->delete($key);
            }
            $durationSeconds = $ttl;
        } elseif ($ttl !== null) { // Invalid TTL type
            throw new CacheException('Invalid TTL type for APCu. Must be null, int, or DateInterval.');
        }

        $result = apcu_store($internalKey, $value, $durationSeconds);
        if ($result) {
            $this->logger?->info("[APCu Cache] SET for key '$key' (internal: $internalKey) with TTL {$durationSeconds}s.");
        } else {
            $this->logger?->warning("[APCu Cache] FAILED to SET for key '$key' (internal: $internalKey). Check APCu memory limits (apc.shm_size).");
        }
        return $result;
    }

    public function delete(string $key): bool
    {
        $this->validateKey($key);
        $internalKey = $this->getInternalKey($key);
        // apcu_exists can be used before deleting, but apcu_delete handles non-existent keys gracefully.
        $result = apcu_delete($internalKey);
        if ($result) {
            $this->logger?->info("[APCu Cache] DELETED for key '$key' (internal: $internalKey).");
        } else {
            // This might also mean the key didn't exist, which is fine for deleting.
            // However, if apcu_exists($internalKey) was true and delete failed, it's a warning.
            if (apcu_exists($internalKey)) {
                $this->logger?->warning("[APCu Cache] FAILED to DELETE for key '$key' (internal: $internalKey).");
            } else {
                $this->logger?->debug("[APCu Cache] Attempted to delete non-existent key '$key' (internal: $internalKey). Considered success.");
                return true; // PSR-16: deleting non-existent key is true
            }
        }
        return $result;
    }

    public function clear(): bool
    {
        // apcu_clear_cache() clears the entire user cache.
        // This might be too broad if other apps use APCu on the same server,
        // or if you want finer-grained control within your src.
        // For truly namespaced clear, you'd need to iterate over known keys with your prefix
        // (e.g., using ApcuIterator if available and desired) and delete them.
        // For simplicity and general PSR-16 behavior, clearing the user cache is the direct approach.
        $this->logger?->info("[APCu Cache] CLEARED user cache (apcu_clear_cache).");
        return apcu_clear_cache();
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $internalKeys = [];
        $originalKeysMap = [];
        foreach ($keys as $key) {
            $sKey = (string)$key;
            $this->validateKey($sKey);
            $internalKey = $this->getInternalKey($sKey);
            $internalKeys[] = $internalKey;
            $originalKeysMap[$internalKey] = $sKey;
        }

        $fetchedValues = apcu_fetch($internalKeys); // $fetchedValues is associative array [internalKey => value] or false
        $results = [];

        if ($fetchedValues === false) { // If any key fails or none found, apcu_fetch can return false for multiple keys
            $this->logger?->warning("[APCu Cache] apcu_fetch returned false for multiple keys.");
            // Fallback to default for all
            foreach ($originalKeysMap as $internalKey => $originalKey) {
                $results[$originalKey] = $default;
            }
            return $results;
        }

        // Initialize all keys with default
        foreach ($originalKeysMap as $originalKey) {
            $results[$originalKey] = $default;
        }

        // Populate with fetched values
        foreach ($fetchedValues as $internalKey => $value) {
            if (isset($originalKeysMap[$internalKey])) {
                $results[$originalKeysMap[$internalKey]] = $value;
            }
        }
        return $results;
    }

    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        $entries = [];
        $durationSeconds = 0; // Default: 0 means cache "forever"
        if ($ttl instanceof DateInterval) {
            $now = new DateTimeImmutable();
            $expires = $now->add($ttl);
            $durationSeconds = max(0, $expires->getTimestamp() - $now->getTimestamp());
        } elseif (is_int($ttl)) {
            if ($ttl <= 0) {
                // If TTL is <=0, PSR-16 implies deletion. We'll delete them individually.
                $keysToDelete = [];
                foreach ($values as $key => $value) $keysToDelete[] = (string)$key;
                return $this->deleteMultiple($keysToDelete);
            }
            $durationSeconds = $ttl;
        } elseif ($ttl !== null) {
            throw new CacheException('Invalid TTL type for APCu.');
        }

        foreach ($values as $key => $value) {
            $sKey = (string)$key;
            $this->validateKey($sKey);
            $entries[$this->getInternalKey($sKey)] = $value;
        }

        if (empty($entries)) return true;

        $errors = apcu_store($entries, null, $durationSeconds); // Second param null when $entries are an array

        if (empty($errors)) { // apcu_store returns empty array on success for multiple entries
            $this->logger?->info("[APCu Cache] SET MULTIPLE successful with TTL {$durationSeconds}s for " . count($entries) . " items.");
            return true;
        } else {
            $this->logger?->warning("[APCu Cache] FAILED to SET MULTIPLE for some keys.", ['failed_keys_internal_indices' => $errors]);
            return false; // Indicates some or all failed
        }
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $internalKeys = [];
        foreach ($keys as $key) {
            $sKey = (string)$key;
            $this->validateKey($sKey);
            $internalKeys[] = $this->getInternalKey($sKey);
        }
        if (empty($internalKeys)) return true;

        // apcu_delete can take an array of keys or an APCUIterator
        $result = apcu_delete($internalKeys); // Returns array of failed keys or true on success

        if ($result === true || (is_array($result) && empty($result))) {
            $this->logger?->info("[APCu Cache] DELETED MULTIPLE successful for " . count($internalKeys) . " keys.");
            return true;
        } else {
            $this->logger?->warning("[APCu Cache] FAILED to DELETE MULTIPLE for some keys.", ['failed_keys_internal' => $result]);
            return false; // Some keys failed to delete
        }
    }

    public function has(string $key): bool
    {
        $this->validateKey($key);
        $internalKey = $this->getInternalKey($key);
        return apcu_exists($internalKey);
    }
}
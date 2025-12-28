<?php

declare(strict_types=1);

namespace Strux\Component\Cache\Drivers;

use DateInterval;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Strux\Component\Exceptions\CacheException;

class FilesystemCache implements CacheInterface
{
    protected string $cacheDir;
    protected string $fileExtension = '.cache';
    protected string $salt;
    protected ?LoggerInterface $logger;

    public function __construct(array $config, ?LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        $this->cacheDir = rtrim($config['path'] ?? sys_get_temp_dir() . '/app_cache_fs', DIRECTORY_SEPARATOR);
        $this->salt = $config['salt'] ?? 'default_filesystem_salt';

        if (!is_dir($this->cacheDir)) {
            if (!mkdir($this->cacheDir, 0755, true)) {
                $this->logger?->error("Failed to create cache directory: $this->cacheDir");
                throw new CacheException("Cannot create cache directory: $this->cacheDir");
            }
        } elseif (!is_writable($this->cacheDir)) {
            $this->logger?->error("Cache directory is not writable: $this->cacheDir");
            throw new CacheException("Cache directory is not writable: $this->cacheDir");
        }
    }

    protected function validateKey(string $key): void
    {
        if ($key === '') {
            throw new CacheException('Cache key cannot be empty.');
        }
        if (preg_match('/[{}()\/\\\\@:]/', $key)) {
            throw new CacheException("Invalid character in cache key: $key. PSR-16 disallows { } ( ) / \\ @ :");
        }
    }

    protected function getCacheFile(string $key): string
    {
        $hashedKey = sha1($key . $this->salt);
        return $this->cacheDir . DIRECTORY_SEPARATOR . $hashedKey . $this->fileExtension;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->validateKey($key);
        $filePath = $this->getCacheFile($key);

        if (!file_exists($filePath)) {
            $this->logger?->debug("[FS Cache] MISS for key '$key'. File: {$filePath}");
            return $default;
        }
        // ... (Rest of the get logic: file_get_contents, json_decode, ttl check from your previous Cache.php)
        // For brevity, I'll assume you move the robust get logic here.
        // This is a simplified placeholder for that logic:
        $content = file_get_contents($filePath);
        if ($content === false) return $default;
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data) || !isset($data['ttl']) || !array_key_exists('value', $data)) {
            @unlink($filePath);
            return $default;
        }
        if (null !== $data['ttl'] && time() > $data['ttl']) {
            @unlink($filePath);
            return $default;
        }
        return $data['value'];
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->validateKey($key);
        $filePath = $this->getCacheFile($key);
        // ... (Rest of the set logic: parseTTL, json_encode, atomic file_put_contents via temp file + rename)
        // For brevity, I'll assume you move the robust set logic here.
        // This is a simplified placeholder for that logic:
        $expiresAt = null;
        if ($ttl !== null) {
            if ($ttl instanceof DateInterval) $expiresAt = (new DateTimeImmutable())->add($ttl)->getTimestamp();
            elseif (is_int($ttl)) {
                if ($ttl <= 0) return $this->delete($key);
                $expiresAt = time() + $ttl;
            } else throw new CacheException('Invalid TTL type.');
        }
        $encodedData = json_encode(['value' => $value, 'ttl' => $expiresAt]);
        if ($encodedData === false) return false;
        $tempFilePath = $filePath . '.' . uniqid('', true) . '.tmp';
        if (file_put_contents($tempFilePath, $encodedData) === false) {
            @unlink($tempFilePath);
            return false;
        }
        if (!rename($tempFilePath, $filePath)) {
            @unlink($tempFilePath);
            return false;
        }
        return true;
    }

    public function delete(string $key): bool
    {
        $this->validateKey($key);
        $filePath = $this->getCacheFile($key);
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return true; // PSR-16: deleting non-existent key is true
    }

    public function clear(): bool
    {
        $files = glob($this->cacheDir . DIRECTORY_SEPARATOR . '*' . $this->fileExtension);
        $success = true;
        if ($files === false) return false;
        foreach ($files as $file) {
            if (is_file($file) && !unlink($file)) $success = false;
        }
        return $success;
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
        $filePath = $this->getCacheFile($key);
        if (!file_exists($filePath)) return false;
        // Simplified: For full check, needs to read and check TTL like in get()
        // For true PSR-16 `has`, you'd need to check expiration without returning value.
        $value = $this->get($key, $this); // Use unique default to see if it was a miss
        return $value !== $this;
    }
}
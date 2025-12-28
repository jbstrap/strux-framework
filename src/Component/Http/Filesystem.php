<?php

declare(strict_types=1);

namespace Strux\Component\Http;

use RuntimeException;
use Strux\Component\Config\Config;

class Filesystem
{
    private Config $config;
    private array $diskConfig;
    private string $activeDiskName;

    public function __construct(?string $disk = null)
    {
        $this->config = container(Config::class);
        $this->disk($disk);
    }

    /**
     * Set the active disk for the filesystem instance.
     */
    public function disk(?string $name = null): self
    {
        $this->activeDiskName = $name ?: $this->config->get('filesystems.default', 'local');
        $this->diskConfig = $this->config->get("filesystems.disks.{$this->activeDiskName}");

        if (!$this->diskConfig) {
            throw new \InvalidArgumentException("Filesystem disk [{$this->activeDiskName}] is not configured.");
        }
        return $this;
    }

    /**
     * Get the fully-qualified path for a file on the active disk.
     */
    public function path(string $path): string
    {
        $root = rtrim($this->diskConfig['root'], '/\\');
        return $root . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
    }

    /**
     * Get the URL for a file on a web disk.
     * Throws an exception if called on a non-web disk.
     */
    public function url(string $path): string
    {
        if (empty($this->diskConfig['url'])) {
            throw new RuntimeException("The [{$this->activeDiskName}] disk does not have a configured URL.");
        }
        $baseUrl = rtrim($this->diskConfig['url'], '/');
        return $baseUrl . '/' . ltrim($path, '/');
    }

    /**
     * Write contents to a file.
     */
    public function put(string $path, string $contents): bool
    {
        $fullPath = $this->path($path);
        $directory = dirname($fullPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        return file_put_contents($fullPath, $contents) !== false;
    }

    /**
     * Get the contents of a file.
     */
    public function get(string $path): ?string
    {
        if (!$this->exists($path)) {
            return null;
        }
        return file_get_contents($this->path($path));
    }

    /**
     * Check if a file exists.
     */
    public function exists(string $path): bool
    {
        return file_exists($this->path($path));
    }

    /**
     * Delete a file.
     */
    public function delete(string $path): bool
    {
        if (!$this->exists($path)) {
            return true; // Deleting a non-existent file is a success
        }
        return unlink($this->path($path));
    }
}
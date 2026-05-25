<?php

declare(strict_types=1);

namespace Strux\Component\Filesystem;

interface FilesystemInterface
{
    /**
     * Set the active disk for the filesystem instance.
     */
    public function disk(?string $name = null): self;

    /**
     * Get the fully-qualified path for a file on the active disk.
     */
    public function path(string $path): string;

    /**
     * Get the URL for a file on a web disk.
     */
    public function url(string $path): string;

    /**
     * Write contents to a file.
     */
    public function put(string $path, string $contents): bool;

    /**
     * Get the contents of a file.
     */
    public function get(string $path): ?string;

    /**
     * Check if a file exists.
     */
    public function exists(string $path): bool;

    /**
     * Delete a file.
     */
    public function delete(string $path): bool;
}

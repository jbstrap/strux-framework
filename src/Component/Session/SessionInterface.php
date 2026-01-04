<?php

declare(strict_types=1);

namespace Strux\Component\Session;

interface SessionInterface
{
    /**
     * Retrieve an item from the session by key.
     * Supports dot notation for nested items.
     *
     * @param string $key The key of the item to retrieve.
     * @param mixed $default Default value to return if the key does not exist.
     * @return mixed The value of the session item or default.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Store an item in the session by key.
     * Supports dot notation for nested items.
     *
     * @param string $key The key to store the item under.
     * @param mixed $value The value to store.
     * @return void
     */
    public function set(string $key, mixed $value): void;

    /**
     * Check if an item exists in the session by key.
     * Supports dot notation for nested items.
     *
     * @param string $key The key to check.
     * @return bool True if the item exists, false otherwise.
     */
    public function has(string $key): bool;

    /**
     * Remove an item from the session by key.
     * Supports dot notation for nested items.
     *
     * @param string $key The key of the item to remove.
     * @return void
     */
    public function remove(string $key): void;

    /**
     * Get all items from the session.
     *
     * @return array An array of all session items.
     */
    public function all(): array;

    /**
     * Append data to an array item in the session.
     * If the key does not exist or is not an array, it will be created as an array.
     * Supports dot notation for nested items.
     *
     * @param string $key The key of the array item.
     * @param mixed $value The value to append.
     * @return void
     */
    public function append(string $key, mixed $value): void;

    /**
     * Destroy the current session.
     *
     * @return void
     */
    public function destroy(): void;

    /**
     * Regenerate the session ID.
     *
     * @param bool $deleteOldSessionSession Whether to delete the old associated session file or not.
     * @return bool True on success, false on failure.
     */
    public function regenerateId(bool $deleteOldSessionSession = false): bool;

    /**
     * Get the current session ID.
     * @return string|false
     */
    public function getId(): string|false;

    /**
     * Get a value from the session and then remove it (flash message).
     * Supports dot notation.
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function pull(string $key, mixed $default = null): mixed;
}
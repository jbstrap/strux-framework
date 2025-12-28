<?php

declare(strict_types=1);

namespace Strux\Support\Helpers;

interface FlashServiceInterface
{
    /**
     * Set a flash message.
     *
     * @param string $key The key for the message.
     * @param mixed $message The message content (string or array of strings).
     * @return void
     */
    public function set(string $key, mixed $message): void;

    /**
     * Check if a flash message exists for the given key.
     * This does not remove the message.
     *
     * @param string $key The key to check.
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Get a flash message by key and remove it.
     *
     * @param string $key The key of the message.
     * @param mixed|null $default Default value if the key doesn't exist.
     * @return mixed The message content or default.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Displays a flash message (or messages) to the user, formatted as HTML.
     * This will retrieve and remove the message(s) from the session.
     *
     * @param string|array $key The key of the message or an array of keys.
     * If array, can be ['key1', 'key2'] or ['key1' => 'type1', 'key2' => 'type2']
     * @param string $defaultType The default alert type if not specified per key.
     * @param bool $withAlert Whether to wrap messages in an alert div.
     * @return string HTML output.
     */
    public function show(string|array $key, string $defaultType = 'info', bool $withAlert = true): string;
}
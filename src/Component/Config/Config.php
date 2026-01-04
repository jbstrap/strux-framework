<?php

namespace Strux\Component\Config;

use ArrayAccess;

class Config implements ArrayAccess
{
    /**
     * All of our configuration items
     *
     * @var array<string,mixed>
     */
    private array $items = [];

    /**
     * @param array<string,mixed> $items Initial etc (e.g., from $_SERVER, $_ENV or a parsed etc file)
     */
    public function __construct(array $items = [])
    {
        // Merge in $_SERVER, so you can also read server vars
        $this->items = array_merge($items, $_SERVER, $_ENV);
    }

    /**
     * Get the specified configuration value.
     * Returns $default if the key does not exist.
     * Supports “dot” notation.
     *
     * @param string $key
     * @param mixed $default
     * @param mixed|null $type
     * @return mixed
     */
    public function get(string $key, mixed $default = null, mixed $type = null): mixed
    {
        $segments = explode('.', $key);
        $config = $this->items;

        foreach ($segments as $segment) {
            if (is_array($config) && array_key_exists($segment, $config)) {
                $config = $config[$segment];
            } else {
                return $default;
            }
        }

        // If a type is specified, convert the value to that type
        if ($type !== null) {
            return match ($type) {
                'int' => (int)$config,
                'float' => (float)$config,
                'bool' => (bool)$config,
                'array' => (array)$config,
                'string' => (string)$config,
                default => $config
            };
        }
        return $config ?? $default;
    }

    /**
     * Set a configuration value using dot notation.
     * Overwrites existing values and converts intermediate non-array values to arrays.
     *
     * @param string $key
     * @param mixed $value
     */
    public function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $current = &$this->items;

        while (count($segments) > 1) {
            $segment = array_shift($segments);

            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }

            $current = &$current[$segment];
        }

        $lastSegment = array_shift($segments);
        $current[$lastSegment] = $value;
    }

    /**
     * Determine if the given configuration value exists.
     * Supports “dot” notation for nested arrays.
     */
    public function has(string $key): bool
    {
        $segments = explode('.', $key);
        $config = $this->items;

        foreach ($segments as $segment) {
            if (is_array($config) && array_key_exists($segment, $config)) {
                $config = $config[$segment];
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Remove a configuration value using dot notation.
     * Does nothing if the key does not exist.
     *
     * @param string $key
     */
    public function remove(string $key): void
    {
        $segments = explode('.', $key);
        $current = &$this->items;
        $lastSegment = array_pop($segments);

        foreach ($segments as $segment) {
            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                return;
            }
            $current = &$current[$segment];
        }

        if (isset($current[$lastSegment])) {
            unset($current[$lastSegment]);
        }
    }

    /**
     * Allow array-style access: $etc['foo.bar']
     */
    public function offsetExists($offset): bool
    {
        return $this->has((string)$offset);
    }

    public function offsetGet($offset): mixed
    {
        return $this->get((string)$offset);
    }

    public function offsetSet($offset, $value): void
    {
        $this->set((string)$offset, $value);
    }

    public function offsetUnset($offset): void
    {
        $this->remove((string)$offset);
    }
}

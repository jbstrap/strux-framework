<?php

declare(strict_types=1);

namespace Strux\Auth;

use Strux\Support\ContainerBridge;

class Auth
{
    protected static function getAuthManager(): AuthManager
    {
        return ContainerBridge::resolve(AuthManager::class);
    }

    public static function sentinel(string $name = null): SentinelInterface
    {
        return self::getAuthManager()->sentinel($name);
    }

    /**
     * Attempt to authenticate using credentials.
     * Use this in your Login Controller.
     */
    public static function attempt(array $credentials): bool
    {
        return self::sentinel('web')->attempt($credentials);
    }

    /**
     * Validate credentials without logging in.
     */
    public static function validate(array $credentials): bool
    {
        return self::sentinel('web')->validate($credentials);
    }

    /**
     * Manually log in a specific user object.
     * Useful after registration (auto-login).
     */
    public static function login(object $user): void
    {
        self::sentinel('web')->login($user);
    }

    public static function logout(): void
    {
        self::sentinel('web')->logout();
    }

    public static function user(): ?object
    {
        return self::sentinel('web')->user();
    }

    public static function check(): bool
    {
        return self::sentinel('web')->check();
    }

    public static function id(): int|string|null
    {
        return self::sentinel('web')->id();
    }
}
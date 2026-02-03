<?php

declare(strict_types=1);

namespace Strux\Support\Bridge;

use Strux\Component\Session\SessionInterface;
use Strux\Support\FrameworkBridge;

/**
 * @method static void start()
 * @method static mixed get(string $key, mixed $default = null)
 * @method static void set(string $key, mixed $value)
 * @method static bool has(string $key)
 * @method static void remove(string $key)
 * @method static array all()
 * @method static void append(string $key, mixed $value)
 * @method static void destroy()
 * @method static bool regenerateId(bool $deleteOldSession = true)
 * @method static string|false getId()
 * @method static mixed pull(string $key, mixed $default = null)
 * @see \Strux\Component\Session\SessionManager
 */
class Session extends FrameworkBridge
{
    /**
     * @inheritDoc
     */
    protected static function getAccessor(): string
    {
        return SessionInterface::class;
    }
}
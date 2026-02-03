<?php

declare(strict_types=1);

namespace Strux\Support\Bridge;

use Strux\Support\FrameworkBridge;

/**
 * @method static mixed get(string $key, mixed $default = null, mixed $type = null)
 * @method static void set(string $key, mixed $value)
 * @method static bool has(string $key)
 * @method static void remove(string $key)
 * @method static array all()
 * @see \Strux\Component\Config\Config
 */
class Config extends FrameworkBridge
{
    /**
     * @inheritDoc
     */
    protected static function getAccessor(): string
    {
        return \Strux\Component\Config\Config::class;
    }
}
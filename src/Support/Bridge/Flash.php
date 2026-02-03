<?php

declare(strict_types=1);

namespace Strux\Support\Bridge;

use Strux\Support\FrameworkBridge;
use Strux\Support\Helpers\FlashInterface;

/**
 * @method static mixed get(string $key, mixed $default = null)
 * @method static void set(string $key, mixed $message)
 * @method static bool has(string $key)
 * @method static string show(string|array $key, string $defaultType = 'info', bool $withAlert = true)
 * @see \Strux\Support\Helpers\Flash
 */
class Flash extends FrameworkBridge
{
    /**
     * @inheritDoc
     */
    protected static function getAccessor(): string
    {
        return FlashInterface::class;
    }
}
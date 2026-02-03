<?php

declare(strict_types=1);

namespace Strux\Support\Bridge;

use Psr\Http\Message\ResponseInterface as Psr7ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface as Psr17StreamFactoryInterface;
use Strux\Support\FrameworkBridge;

/**
 * @method static \Strux\Component\Http\Response setContent(string $content)
 * @method static \Strux\Component\Http\Response setStatusCode(int $code)
 * @method static \Strux\Component\Http\Response setHeader(string $name, string|array $value, bool $replace = true)
 * @method static \Strux\Component\Http\Response addHeader(string $name, string|array $value)
 * @method static \Strux\Component\Http\Response json(mixed $data, int $status = 200, array $headers = [], int $options = 0)
 * @method static \Strux\Component\Http\Response redirect(string $url, int $status = 302)
 * @method static \Strux\Component\Http\Response noCache()
 * @method static \Strux\Component\Http\Response setCache(array $options = [])
 * @method static \Strux\Component\Http\Response setLastModified($date)
 * @method static Psr7ResponseInterface toPsr7Response(Psr17StreamFactoryInterface $psr17StreamFactory)
 * @see \Strux\Component\Http\Response
 */
class Response extends FrameworkBridge
{
    /**
     * @inheritDoc
     */
    protected static function getAccessor(): string
    {
        return \Strux\Component\Http\Response::class;
    }
}
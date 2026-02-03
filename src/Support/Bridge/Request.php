<?php

declare(strict_types=1);

namespace Strux\Support\Bridge;

use Psr\Http\Message\UriInterface;
use Strux\Component\Http\SafeInput;
use Strux\Component\Http\UploadedFile;
use Strux\Support\FrameworkBridge;

/**
 * @method static string getMethod()
 * @method static string method()
 * @method static UriInterface getUri()
 * @method static string getPath()
 * @method static mixed input(string $key, mixed $default = null, ?string $type = null)
 * @method static bool has(string|array $key)
 * @method static mixed query(string $key, mixed $default = null, ?string $type = null)
 * @method static SafeInput safe()
 * @method static array|object|null allPost()
 * @method static array allQuery()
 * @method static array all()
 * @method static array headers()
 * @method static array|string|null header(string $name)
 * @method static array safeAllPost()
 * @method static array safeAllQuery()
 * @method static array safeAll()
 * @method static mixed server(string $key, mixed $default = null)
 * @method static mixed cookie(string $key, mixed $default = null)
 * @method static array cookies()
 * @method static UploadedFile|array|null file(string $key)
 * @method static bool hasFile(string $key)
 * @method static mixed routeParam(string $name, mixed $default = null)
 * @method static array routeParams()
 * @method static bool isAjax()
 * @method static bool isSecure()
 * @method static mixed getAttribute()
 * @method static bool is(string $method)
 * @method static string path()
 * @method static bool isPath(string $pattern)
 * @method static ?object getJson()
 * @method static string getRefer()
 * @method static string getReferrer()
 * @method static mixed castValue(mixed $value, string $type)
 * @see \Strux\Component\Http\Request
 */
class Request extends FrameworkBridge
{
    /**
     * @inheritDoc
     */
    protected static function getAccessor(): string
    {
        return \Strux\Component\Http\Request::class;
    }
}
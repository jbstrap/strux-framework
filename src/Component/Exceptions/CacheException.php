<?php

namespace Strux\Component\Exceptions;

use Psr\SimpleCache\CacheException as PsrCacheException;
use RuntimeException;

class CacheException extends RuntimeException implements PsrCacheException
{
}
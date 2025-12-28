<?php

declare(strict_types=1);

namespace Strux\Component\Http\Psr7;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class Response implements ResponseInterface
{
    use MessageTrait;
    use ResponseTrait;

    public function __construct(
        int              $status = 200,
        array            $headers = [],
        ?StreamInterface $body = null,
        string           $version = '1.1',
        ?string          $reason = null
    )
    {
        $this->statusCode = $status;
        $this->setHeaders($headers);
        $this->body = $body;
        $this->protocolVersion = $version;
        $this->reasonPhrase = $reason ?? self::$statusTexts[$this->statusCode] ?? 'Unknown status';
    }
}

<?php

declare(strict_types=1);

namespace Strux\Component\Http\Psr7;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class Request implements RequestInterface
{
    use MessageTrait;
    use RequestTrait;

    public function __construct(
        string           $method,
                         $uri,
        array            $headers = [],
        ?StreamInterface $body = null,
        string           $version = '1.1'
    )
    {
        if (!($uri instanceof UriInterface)) {
            $uri = new Uri($uri);
        }
        $this->method = $this->filterMethod($method);
        $this->uri = $uri;
        $this->setHeaders($headers);
        $this->protocolVersion = $version;
        if (!$this->hasHeader('Host')) {
            $this->updateHostFromUri();
        }
        if ($body !== null) {
            $this->body = $body;
        }
    }
}

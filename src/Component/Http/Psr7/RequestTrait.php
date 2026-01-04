<?php

declare(strict_types=1);

namespace Strux\Component\Http\Psr7;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;

trait RequestTrait
{
    private string $method;
    private ?string $requestTarget = null;
    private ?UriInterface $uri = null;

    public function getRequestTarget(): string
    {
        if ($this->requestTarget !== null) {
            return $this->requestTarget;
        }
        if ($this->uri === null) {
            return '/';
        }
        $target = $this->uri->getPath();
        if ($this->uri->getQuery()) {
            $target .= '?' . $this->uri->getQuery();
        }
        if ($target === '') {
            $target = '/';
        }
        return $target;
    }

    public function withRequestTarget(string $requestTarget): static
    {
        if (preg_match('#\s#', $requestTarget)) {
            throw new InvalidArgumentException('Invalid request target provided; must not contain whitespace.');
        }
        $new = clone $this;
        $new->requestTarget = $requestTarget;
        return $new;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod(string $method): static
    {
        $new = clone $this;
        $new->method = $new->filterMethod($method);
        return $new;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): static
    {
        if ($uri === $this->uri) {
            return $this;
        }
        $new = clone $this;
        $new->uri = $uri;
        if (!$preserveHost || !$this->hasHeader('Host')) {
            $new->updateHostFromUri();
        }
        return $new;
    }

    private function updateHostFromUri(): void
    {
        $host = $this->uri->getHost();
        if ($host === '') {
            return;
        }
        if (($port = $this->uri->getPort()) !== null) {
            $host .= ':' . $port;
        }
        if (isset($this->headerNames['host'])) {
            $header = $this->headerNames['host'];
            unset($this->headers[$header]);
        }
        $this->headerNames['host'] = 'Host';
        $this->headers['Host'] = [$host];
    }

    private function filterMethod(string $method): string
    {
        if (!preg_match('/^[!#$%&\'*+-.^_`|~0-9a-zA-Z]+$/', $method)) {
            throw new InvalidArgumentException(sprintf('Unsupported HTTP method "%s" provided.', $method));
        }
        return $method;
    }
}

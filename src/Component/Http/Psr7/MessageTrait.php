<?php

declare(strict_types=1);

namespace Strux\Component\Http\Psr7;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;

trait MessageTrait
{
    private string $protocolVersion = '1.1';
    private array $headers = [];
    private array $headerNames = []; // For case-insensitive header lookups
    private ?StreamInterface $body = null;

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion(string $version): static
    {
        if ($this->protocolVersion === $version) {
            return $this;
        }
        $new = clone $this;
        $new->protocolVersion = $version;
        return $new;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headerNames[strtolower($name)]);
    }

    /**
     * Retrieves a message header value by the given case-insensitive name.
     *
     * @param string $name Case-insensitive header field name.
     * @return string[] An array of string values as provided for the given
     * header. If the header does not appear in the message, this method MUST
     * return an empty array.
     */
    public function getHeader(string $name): array
    {
        $normalized = strtolower($name);
        if (!isset($this->headerNames[$normalized])) {
            return [];
        }

        $header = $this->headerNames[$normalized];
        return $this->headers[$header];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader(string $name, $value): static
    {
        $this->validateHeader($name);
        $value = $this->normalizeHeaderValue($value);
        $normalized = strtolower($name);

        $new = clone $this;
        if (isset($new->headerNames[$normalized])) {
            unset($new->headers[$new->headerNames[$normalized]]);
        }
        $new->headerNames[$normalized] = $name;
        $new->headers[$name] = $value;

        return $new;
    }

    public function withAddedHeader(string $name, $value): static
    {
        $this->validateHeader($name);
        if (!$this->hasHeader($name)) {
            return $this->withHeader($name, $value);
        }

        $normalized = strtolower($name);
        $header = $this->headerNames[$normalized];
        $value = $this->normalizeHeaderValue($value);

        $new = clone $this;
        $new->headers[$header] = array_merge($this->headers[$header], $value);
        return $new;
    }

    public function withoutHeader(string $name): static
    {
        $normalized = strtolower($name);
        if (!isset($this->headerNames[$normalized])) {
            return $this;
        }

        $header = $this->headerNames[$normalized];
        $new = clone $this;
        unset($new->headers[$header], $new->headerNames[$normalized]);
        return $new;
    }

    public function getBody(): StreamInterface
    {
        if ($this->body === null) {
            $this->body = new Stream(fopen('php://temp', 'r+'));
        }
        return $this->body;
    }

    public function withBody(StreamInterface $body): static
    {
        if ($body === $this->body) {
            return $this;
        }
        $new = clone $this;
        $new->body = $body;
        return $new;
    }

    protected function setHeaders(array $headers): void
    {
        $this->headerNames = $this->headers = [];
        foreach ($headers as $name => $value) {
            $this->validateHeader($name);
            $value = $this->normalizeHeaderValue($value);
            $normalized = strtolower($name);
            $this->headerNames[$normalized] = $name;
            $this->headers[$name] = $value;
        }
    }

    private function normalizeHeaderValue($value): array
    {
        if (!is_array($value)) {
            $value = [$value];
        }
        if (empty($value)) {
            throw new InvalidArgumentException('Header value can not be an empty array.');
        }
        foreach ($value as $v) {
            if (!is_string($v) && !is_numeric($v)) {
                throw new InvalidArgumentException('Invalid header value type.');
            }
        }
        return $value;
    }

    private function validateHeader(string $name): void
    {
        if (!preg_match('/^[a-zA-Z0-9\'`#$%&*+.^_|~!-]+$/', $name)) {
            throw new InvalidArgumentException('Invalid header name.');
        }
    }
}

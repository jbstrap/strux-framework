<?php

declare(strict_types=1);

namespace Strux\Component\Http\Psr7;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;

class Uri implements UriInterface
{
    private string $scheme = '';
    private string $userInfo = '';
    private string $host = '';
    private ?int $port = null;
    private string $path = '';
    private string $query = '';
    private string $fragment = '';
    private const SCHEME_PORTS = ['http' => 80, 'https' => 443];

    public function __construct(string $uri = '')
    {
        if ($uri !== '') {
            $parts = parse_url($uri);
            if ($parts === false) throw new InvalidArgumentException("Unable to parse URI: $uri");
            $this->applyParts($parts);
        }
    }

    public function __toString(): string
    {
        return self::createUriString($this->scheme, $this->getAuthority(), $this->path, $this->query, $this->fragment);
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getAuthority(): string
    {
        if ($this->host === '') return '';
        $authority = $this->host;
        if ($this->userInfo !== '') $authority = $this->userInfo . '@' . $authority;
        if ($this->port !== null) $authority .= ':' . $this->port;
        return $authority;
    }

    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    public function withScheme(string $scheme): static
    {
        $scheme = $this->filterScheme($scheme);
        if ($this->scheme === $scheme) return $this;
        $new = clone $this;
        $new->scheme = $scheme;
        $new->port = $new->filterPort($new->port);
        return $new;
    }

    public function withUserInfo(string $user, ?string $password = null): static
    {
        $info = $user;
        if ($password !== null) $info .= ':' . $password;
        if ($this->userInfo === $info) return $this;
        $new = clone $this;
        $new->userInfo = $info;
        return $new;
    }

    public function withHost(string $host): static
    {
        if ($this->host === strtolower($host)) return $this;
        $new = clone $this;
        $new->host = strtolower($host);
        return $new;
    }

    public function withPort(?int $port): static
    {
        $port = $this->filterPort($port);
        if ($this->port === $port) return $this;
        $new = clone $this;
        $new->port = $port;
        return $new;
    }

    public function withPath(string $path): static
    {
        $path = $this->filterPath($path);
        if ($this->path === $path) return $this;
        $new = clone $this;
        $new->path = $path;
        return $new;
    }

    public function withQuery(string $query): static
    {
        $query = $this->filterQueryAndFragment($query);
        if ($this->query === $query) return $this;
        $new = clone $this;
        $new->query = $query;
        return $new;
    }

    public function withFragment(string $fragment): static
    {
        $fragment = $this->filterQueryAndFragment($fragment);
        if ($this->fragment === $fragment) return $this;
        $new = clone $this;
        $new->fragment = $fragment;
        return $new;
    }

    private function applyParts(array $parts): void
    {
        $this->scheme = isset($parts['scheme']) ? $this->filterScheme($parts['scheme']) : '';
        $this->userInfo = $parts['user'] ?? '';
        if (isset($parts['pass'])) $this->userInfo .= ':' . $parts['pass'];
        $this->host = isset($parts['host']) ? strtolower($parts['host']) : '';
        $this->port = isset($parts['port']) ? $this->filterPort($parts['port']) : null;
        $this->path = isset($parts['path']) ? $this->filterPath($parts['path']) : '';
        $this->query = isset($parts['query']) ? $this->filterQueryAndFragment($parts['query']) : '';
        $this->fragment = isset($parts['fragment']) ? $this->filterQueryAndFragment($parts['fragment']) : '';
    }

    private function filterScheme(string $scheme): string
    {
        return strtolower(str_replace('://', '', $scheme));
    }

    private function filterPort(?int $port): ?int
    {
        if ($port === null) return null;
        if ($port < 1 || $port > 65535) throw new InvalidArgumentException("Invalid port: $port. Must be between 1 and 65535.");
        if (isset(self::SCHEME_PORTS[$this->scheme]) && $port === self::SCHEME_PORTS[$this->scheme]) return null;
        return $port;
    }

    private function filterPath(string $path): string
    {
        return preg_replace_callback('/(?:[^a-zA-Z0-9_\-\.~:@&=\+\$,\/;%]+|%(?![A-Fa-f0-9]{2}))/', fn($m) => rawurlencode($m[0]), $path);
    }

    private function filterQueryAndFragment(string $str): string
    {
        return preg_replace_callback('/(?:[^a-zA-Z0-9_\-\.~!\$&\'\(\)\*\+,;=%:@\/\?]+|%(?![A-Fa-f0-9]{2}))/', fn($m) => rawurlencode($m[0]), $str);
    }

    private static function createUriString(string $scheme, string $authority, string $path, string $query, string $fragment): string
    {
        $uri = '';
        if ($scheme !== '') $uri .= $scheme . ':';
        if ($authority !== '') $uri .= '//' . $authority;
        if ($path !== '' && !str_starts_with($path, '/')) $path = '/' . $path;
        $uri .= $path;
        if ($query !== '') $uri .= '?' . $query;
        if ($fragment !== '') $uri .= '#' . $fragment;
        return $uri;
    }
}

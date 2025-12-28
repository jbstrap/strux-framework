<?php

declare(strict_types=1);

namespace Strux\Component\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;
use Strux\Component\Http\Traits\SanitizesData;

/**
 * Class Request
 *
 * A wrapper around a PSR-7 ServerRequestInterface, providing a convenient API
 * for accessing request data.
 */
class Request
{
    use SanitizesData;

    private ServerRequestInterface $request;
    private ?SafeInput $safeInstance = null;

    /**
     * Request constructor.
     *
     * @param ServerRequestInterface $psrRequest The underlying PSR-7 ServerRequest.
     */
    public function __construct(ServerRequestInterface $psrRequest)
    {
        $this->request = $psrRequest;
    }

    /**
     * Get the request method.
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->request->getMethod();
    }

    /**
     * Alias for getMethod()
     *
     * @return string
     */
    public function method(): string
    {
        return $this->getMethod();
    }

    /**
     * Get the request URI object.
     *
     * @return UriInterface
     */
    public function getUri(): UriInterface
    {
        return $this->request->getUri();
    }

    /**
     * Get the request path.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->request->getUri()->getPath();
    }

    /**
     * Retrieve an input item from the request.
     */
    public function input(string $key, mixed $default = null, ?string $type = null): mixed
    {
        $value = $this->findInArray($key, $this->all(), $default);
        if ($value === null) {
            return $default;
        }
        if ($type !== null && $value !== $default) {
            return $this->castValue($value, $type);
        }
        return $value;
    }

    /**
     * Determine if the request contains a given input item key.
     * Supports dot notation for nested arrays.
     *
     * @param string|array $key
     * @return bool
     */
    public function has(string|array $key): bool
    {
        $keys = is_array($key) ? $key : func_get_args();
        $input = $this->all();

        $sentinel = new \stdClass();

        foreach ($keys as $value) {
            if ($this->findInArray($value, $input, $sentinel) === $sentinel) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get a raw query parameter from GET data. Optionally cast it to a type.
     */
    public function query(string $key, mixed $default = null, ?string $type = null): mixed
    {
        $value = $this->request->getQueryParams()[$key] ?? $default;

        if ($type !== null && $value !== $default) {
            return $this->castValue($value, $type);
        }
        return $value;
    }

    /**
     * Access the sanitized input retriever.
     */
    public function safe(): SafeInput
    {
        if ($this->safeInstance === null) {
            $this->safeInstance = new SafeInput($this);
        }
        return $this->safeInstance;
    }

    /**
     * Get all POST parameters from parsed body.
     * @return array|object|null
     */
    public function allPost(): array|object|null
    {
        return $this->request->getParsedBody() ?? [];
    }

    /**
     * Get all query parameters.
     * @return array
     */
    public function allQuery(): array
    {
        return $this->request->getQueryParams() ?? [];
    }

    /**
     * Get all request data (merges GET, POST, files, cookies).
     */
    public function all(): array
    {
        $body = $this->request->getParsedBody();
        $query = $this->request->getQueryParams();

        if (!is_array($body)) {
            $body = [];
        }

        return array_merge($query, $body);
    }

    /**
     * Get all headers.
     */
    public function headers(): array
    {
        return $this->request->getHeaders();
    }

    /**
     * Get a specific header value by name.
     * Returns null if the header is not present.
     * If multiple values exist, returns an array of values.
     */
    public function header(string $name): array|string|null
    {
        $headerLine = $this->request->getHeaderLine($name);
        if ($headerLine === '') {
            return null;
        }
        $headerValues = $this->request->getHeader($name);
        return count($headerValues) === 1 ? $headerValues[0] : $headerValues;
    }

    /**
     * Get all SANITIZED POST data.
     */
    public function safeAllPost(): array
    {
        return $this->sanitize($this->allPost() ?? []);
    }

    /**
     * Get all SANITIZED GET data.
     */
    public function safeAllQuery(): array
    {
        return $this->sanitize($this->allQuery());
    }

    /**
     * Get all SANITIZED request data (merges GET and POST).
     */
    public function safeAll(): array
    {
        return $this->sanitize($this->all());
    }

    /**
     * Get a server parameter.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function server(string $key, mixed $default = null): mixed
    {
        $serverParams = $this->request->getServerParams();
        return $serverParams[strtoupper($key)] ?? $serverParams[strtolower($key)] ?? $default; // Try common casings
    }

    /**
     * Get a cookie value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function cookie(string $key, mixed $default = null): mixed
    {
        $cookies = $this->request->getCookieParams();
        return $cookies[$key] ?? $default;
    }

    /**
     * Get all cookies.
     *
     * @return array
     */
    public function cookies(): array
    {
        return $this->request->getCookieParams();
    }

    /**
     * Get an uploaded file or array of files by key.
     *
     * @param string $key The key for the uploaded file (e.g., 'attachment').
     * @return UploadedFile|UploadedFile[]|null
     */
    public function file(string $key): UploadedFile|array|null
    {
        $files = $this->request->getUploadedFiles();
        $fileData = $files[$key] ?? null;

        if (!$fileData) {
            return null;
        }

        if (is_array($fileData)) {
            $uploadedFiles = [];
            foreach ($fileData as $file) {
                if ($file instanceof UploadedFileInterface) {
                    $uploadedFiles[] = new UploadedFile($file);
                }
            }
            return empty($uploadedFiles) ? null : $uploadedFiles;
        }

        // Handle single file
        if ($fileData instanceof UploadedFileInterface) {
            return new UploadedFile($fileData);
        }

        return null;
    }

    /**
     * Check if a file exists in the request.
     */
    public function hasFile(string $key): bool
    {
        return isset($this->request->getUploadedFiles()[$key]);
    }

    /**
     * Get a route parameter.
     * Route parameters are expected to be set as attributes on the PSR-7 request.
     *
     * @param string $name The name of the route parameter.
     * @param mixed $default Default value if the parameter is not found.
     * @return mixed
     */
    public function routeParam(string $name, mixed $default = null): mixed
    {
        return $this->request->getAttribute($name, $default);
    }

    /**
     * Get all route parameters (attributes from PSR-7 request).
     *
     * @return array
     */
    public function routeParams(): array
    {
        return $this->request->getAttributes();
    }


    /**
     * Check if the request is an AJAX request (common check).
     *
     * @return bool
     */
    public function isAjax(): bool
    {
        return strtolower($this->request->getHeaderLine('X-Requested-With')) === 'xmlhttprequest';
    }

    /**
     * Check if the request is secure (HTTPS).
     *
     * @return bool
     */
    public function isSecure(): bool
    {
        return $this->request->getUri()->getScheme() === 'https';
    }

    /**
     * Get a request attribute (from PSR-7 request attributes).
     *
     * @param string $name The attribute name.
     * @param mixed $default Default value if attribute not found.
     * @return mixed
     */
    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->request->getAttribute($name, $default);
    }

    /**
     * Check if the request method matches the provided type (get, post, put, etc.).
     * @param string $method
     * @return bool
     */
    public function is(string $method): bool
    {
        return strtoupper($this->request->getMethod()) === strtoupper($method);
    }

    public function path(): string
    {
        return ltrim($this->request->getUri()->getPath(), '/');
    }

    public function isPath(string $pattern): bool
    {
        $path = $this->path();
        // Ensure the pattern is also trimmed if it might contain slashes
        $normalizedPattern = trim($pattern, '/');
        // Simple wildcard: * matches any characters except /
        // For more complex routing, a dedicated routing library with more robust pattern matching is better.
        $regexPattern = str_replace('*', '[^/]*', $normalizedPattern);
        return (bool)preg_match("#^$regexPattern$#i", $path);
    }

    public function getJson(): ?object
    {
        $parsedBody = $this->request->getParsedBody();
        if (is_object($parsedBody)) {
            return $parsedBody; // Assumes body parsing middleware already handled JSON
        }

        // Fallback: if body is a string and looks like JSON,
        // This is less ideal as the body stream should ideally be parsed once.
        $body = $this->request->getBody();
        if (!$body->isReadable()) {
            return null;
        }

        $contents = $body->getContents();
        if ($body->isSeekable()) {
            $body->rewind(); // Rewind if possible, for subsequent reads
        }

        if (empty($contents)) {
            return null;
        }


        $jsonData = json_decode($contents);
        if (json_last_error() === JSON_ERROR_NONE && is_object($jsonData)) {
            return $jsonData;
        }

        // Log error if desired
        // error_log('JSON Decode Error in Request->getJson(): ' . json_last_error_msg());
        return null;
    }

    /**
     * Get the referring URL or fallback to the base URL
     *
     * @return string
     */
    public function getRefer(): string
    {
        $referer = $this->request?->getHeaderLine('Referer');
        if (!empty($referer) && filter_var($referer, FILTER_VALIDATE_URL)) {
            return $referer;
        }
        if (function_exists('url')) {
            $referer = url();
        } else {
            $referer = '/';
        }
        return $referer;
    }

    /**
     * Alias for getRefer()
     *
     * @return string
     */
    public function getReferrer(): string
    {
        return $this->getRefer();
    }

    /**
     * Helper to cast a value to a specific type.
     * Public so it can be used by SafeInput.
     */
    public function castValue(mixed $value, string $type): mixed
    {
        if ($value === null) return null;

        return match (strtolower($type)) {
            'int', 'integer' => (int)$value,
            'str', 'string' => (string)$value,
            'bool', 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'float', 'double' => (float)$value,
            'array' => (array)$value,
            default => $value
        };
    }

    /**
     * Helper to find a value in an array using dot notation.
     */
    private function findInArray(string $key, array $data, mixed $default = null): mixed
    {
        // Optimization: check for direct key first
        if (array_key_exists($key, $data)) {
            return $data[$key];
        }

        if (!str_contains($key, '.')) {
            return $default;
        }

        foreach (explode('.', $key) as $segment) {
            if (is_array($data) && array_key_exists($segment, $data)) {
                $data = $data[$segment];
            } else {
                return $default;
            }
        }

        return $data;
    }
}

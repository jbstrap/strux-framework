<?php

declare(strict_types=1);

namespace Strux\Component\Http\Psr7;

use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriFactoryInterface;

class ServerRequestCreator
{
    private ServerRequestFactoryInterface $serverRequestFactory;
    private UriFactoryInterface $uriFactory;
    private UploadedFileFactoryInterface $uploadedFileFactory;
    private StreamFactoryInterface $streamFactory;

    public function __construct(
        ServerRequestFactoryInterface $serverRequestFactory,
        UriFactoryInterface           $uriFactory,
        UploadedFileFactoryInterface  $uploadedFileFactory,
        StreamFactoryInterface        $streamFactory
    )
    {
        $this->serverRequestFactory = $serverRequestFactory;
        $this->uriFactory = $uriFactory;
        $this->uploadedFileFactory = $uploadedFileFactory;
        $this->streamFactory = $streamFactory;
    }

    public function fromGlobals(): ServerRequestInterface
    {
        $serverParams = $_SERVER;
        $method = $serverParams['REQUEST_METHOD'] ?? 'GET';
        $uri = $this->createUriFromServer($serverParams);
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $cookies = $_COOKIE;
        $body = $this->streamFactory->createStreamFromFile('php://input', 'r');
        $uploadedFiles = $this->normalizeFiles($_FILES);

        $request = $this->serverRequestFactory->createServerRequest($method, $uri, $serverParams);
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        $request = $request
            ->withCookieParams($cookies)
            ->withQueryParams($_GET)
            ->withBody($body)
            ->withUploadedFiles($uploadedFiles);

        if ($method === 'POST') {
            $contentType = $request->getHeaderLine('Content-Type');
            if (stripos($contentType, 'application/x-www-form-urlencoded') === 0 || stripos($contentType, 'multipart/form-data') === 0) {
                $request = $request->withParsedBody($_POST);
            }
        }

        return $request;
    }

    private function createUriFromServer(array $server): Uri
    {
        $uri = $this->uriFactory->createUri('');
        if (isset($server['HTTPS']) && $server['HTTPS'] !== 'off') {
            $uri = $uri->withScheme('https');
        } else {
            $uri = $uri->withScheme('http');
        }

        $host = $server['HTTP_HOST'] ?? $server['SERVER_NAME'] ?? '';
        if (preg_match('/^(.+)\:(\d+)$/', $host, $matches)) {
            $uri = $uri->withHost($matches[1])->withPort((int)$matches[2]);
        } else {
            $uri = $uri->withHost($host);
        }

        $requestUri = $server['REQUEST_URI'] ?? '/';
        $path = explode('?', $requestUri, 2)[0];
        $uri = $uri->withPath($path);

        if (isset($server['QUERY_STRING'])) {
            $uri = $uri->withQuery($server['QUERY_STRING']);
        }

        return $uri;
    }

    private function normalizeFiles(array $files): array
    {
        $normalized = [];
        foreach ($files as $key => $value) {
            if ($value instanceof UploadedFileInterface) {
                $normalized[$key] = $value;
            } elseif (is_array($value) && isset($value['tmp_name'])) {
                if (is_array($value['tmp_name'])) {
                    $normalized[$key] = $this->normalizeNestedFiles($value);
                } else {
                    $normalized[$key] = $this->createUploadedFileFromSpec($value);
                }
            }
        }
        return $normalized;
    }

    private function normalizeNestedFiles(array $files = []): array
    {
        $normalized = [];
        foreach ($files['tmp_name'] as $key => $value) {
            $spec = [
                'tmp_name' => $files['tmp_name'][$key],
                'size' => $files['size'][$key],
                'error' => $files['error'][$key],
                'name' => $files['name'][$key],
                'type' => $files['type'][$key],
            ];
            $normalized[$key] = is_array($spec['tmp_name'])
                ? $this->normalizeNestedFiles($spec)
                : $this->createUploadedFileFromSpec($spec);
        }
        return $normalized;
    }

    private function createUploadedFileFromSpec(array $spec): UploadedFileInterface
    {
        $stream = null;
        // Only attempt to create a stream if the upload was successful.
        if ($spec['error'] === UPLOAD_ERR_OK) {
            try {
                // This can still fail if tmp_name is invalid for some reason.
                $stream = $this->streamFactory->createStreamFromFile($spec['tmp_name']);
            } catch (\RuntimeException $e) {
                // If stream creation fails, treat it as a write error.
                $spec['error'] = UPLOAD_ERR_CANT_WRITE;
            }
        }

        // If there was any error (including NO_FILE) or stream creation failed,
        // create an empty stream for the UploadedFile object.
        if ($stream === null) {
            $stream = $this->streamFactory->createStream();
        }

        return $this->uploadedFileFactory->createUploadedFile(
            $stream,
            $spec['size'] ?? 0,
            $spec['error'],
            $spec['name'] ?? null,
            $spec['type'] ?? null
        );
    }
}

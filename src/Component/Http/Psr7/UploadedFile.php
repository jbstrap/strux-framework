<?php

declare(strict_types=1);

namespace Strux\Component\Http\Psr7;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

class UploadedFile implements UploadedFileInterface
{
    private ?string $clientFilename;
    private ?string $clientMediaType;
    private int $error;
    private ?int $size;
    private ?StreamInterface $stream = null;
    private ?string $file = null;
    private bool $moved = false;

    public function __construct($streamOrFile, int $size, int $error, ?string $clientFilename = null, ?string $clientMediaType = null)
    {
        if ($error < 0 || $error > 8) throw new InvalidArgumentException('Invalid error status for UploadedFile');
        $this->size = $size;
        $this->error = $error;
        $this->clientFilename = $clientFilename;
        $this->clientMediaType = $clientMediaType;

        if ($this->error === UPLOAD_ERR_OK) {
            if (is_string($streamOrFile)) {
                $this->file = $streamOrFile;
            } elseif (is_resource($streamOrFile)) {
                $this->stream = new Stream($streamOrFile);
            } elseif ($streamOrFile instanceof StreamInterface) {
                $this->stream = $streamOrFile;
            } else {
                throw new InvalidArgumentException('Invalid stream or file provided for UploadedFile');
            }
        }
    }

    private function validateActive(): void
    {
        if ($this->moved) {
            throw new RuntimeException('Cannot retrieve stream after it has been moved.');
        }
    }

    public function getStream(): StreamInterface
    {
        $this->validateActive();
        if ($this->stream instanceof StreamInterface) {
            return $this->stream;
        }
        return new Stream(fopen($this->file, 'r'));
    }

    public function moveTo(string $targetPath): void
    {
        $this->validateActive();
        if (!is_string($targetPath) || $targetPath === '') throw new InvalidArgumentException('Invalid path provided for move; must be a non-empty string');
        if ($this->file !== null) {
            $this->moved = rename($this->file, $targetPath);
        } else {
            $stream = $this->getStream();
            if ($stream->isSeekable()) {
                $stream->rewind();
            }
            $destination = new Stream(fopen($targetPath, 'w'));
            while (!$stream->eof()) {
                $destination->write($stream->read(1048576));
            }
            $this->moved = true;
        }
        if (!$this->moved) throw new RuntimeException('Uploaded file could not be moved to ' . $targetPath);
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }
}

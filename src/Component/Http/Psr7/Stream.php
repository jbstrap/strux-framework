<?php

declare(strict_types=1);

namespace Strux\Component\Http\Psr7;

use Psr\Http\Message\StreamInterface;
use RuntimeException;

class Stream implements StreamInterface
{
    private $stream;
    private ?bool $seekable = null;
    private ?bool $readable = null;
    private ?bool $writable = null;
    private ?array $meta = null;
    private ?int $size = null;

    public function __construct($stream)
    {
        if (!is_resource($stream) || get_resource_type($stream) !== 'stream') {
            throw new \InvalidArgumentException('Stream must be a valid stream resource.');
        }
        $this->stream = $stream;
    }

    public function __destruct()
    {
        $this->close();
    }

    public function __toString(): string
    {
        try {
            return $this->getContents();
        } catch (\Throwable $e) {
            return '';
        }
    }

    public function close(): void
    {
        if (isset($this->stream)) {
            @fclose($this->stream);
        }
        $this->detach();
    }

    public function detach()
    {
        if (!isset($this->stream)) {
            return null;
        }
        $resource = $this->stream;
        unset($this->stream);
        $this->size = $this->meta = null;
        $this->readable = $this->writable = $this->seekable = false;
        return $resource;
    }

    public function getSize(): ?int
    {
        if ($this->size !== null) {
            return $this->size;
        }
        if (!isset($this->stream)) {
            return null;
        }
        $stats = fstat($this->stream);
        return $this->size = $stats['size'] ?? null;
    }

    public function tell(): int
    {
        if (!isset($this->stream)) {
            throw new RuntimeException('Stream is detached.');
        }
        $result = ftell($this->stream);
        if ($result === false) {
            throw new RuntimeException('Unable to determine stream position.');
        }
        return $result;
    }

    public function eof(): bool
    {
        return !isset($this->stream) || feof($this->stream);
    }

    public function isSeekable(): bool
    {
        if ($this->seekable !== null) {
            return $this->seekable;
        }
        return $this->seekable = isset($this->stream) && $this->getMetadata('seekable');
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if (!$this->isSeekable()) {
            throw new RuntimeException('Stream is not seekable.');
        }
        if (fseek($this->stream, $offset, $whence) === -1) {
            throw new RuntimeException('Unable to seek to stream position ' . $offset . ' with whence ' . var_export($whence, true));
        }
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        if ($this->writable !== null) {
            return $this->writable;
        }
        return $this->writable = isset($this->stream) && preg_match('/a|w|c|x|\+/', $this->getMetadata('mode'));
    }

    public function write(string $string): int
    {
        if (!$this->isWritable()) {
            throw new RuntimeException('Stream is not writable.');
        }
        $this->size = null;
        $result = fwrite($this->stream, $string);
        if ($result === false) {
            throw new RuntimeException('Unable to write to stream.');
        }
        return $result;
    }

    public function isReadable(): bool
    {
        if ($this->readable !== null) {
            return $this->readable;
        }
        return $this->readable = isset($this->stream) && preg_match('/r|\+/', $this->getMetadata('mode'));
    }

    public function read(int $length): string
    {
        if (!$this->isReadable()) {
            throw new RuntimeException('Stream is not readable.');
        }
        $result = fread($this->stream, $length);
        if ($result === false) {
            throw new RuntimeException('Unable to read from stream.');
        }
        return $result;
    }

    public function getContents(): string
    {
        if (!$this->isReadable()) {
            throw new RuntimeException('Stream is not readable.');
        }
        $contents = stream_get_contents($this->stream);
        if ($contents === false) {
            throw new RuntimeException('Unable to read stream contents.');
        }
        return $contents;
    }

    public function getMetadata(?string $key = null)
    {
        if ($this->meta === null) {
            $this->meta = stream_get_meta_data($this->stream);
        }
        return $key === null ? $this->meta : ($this->meta[$key] ?? null);
    }
}

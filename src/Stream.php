<?php

declare(strict_types=1);

namespace G\Http\Message;

use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * PSR-7
 * @version 1.0.0
 */
class Stream implements StreamInterface
{
    protected const REGEX_READABLE = '/[r\+]/'; // Too strict ? '/^(?:rw?|[rwaxc]\+)[ebt]?$/';
    protected const REGEX_WRITABLE = '/[waxc\+]/'; // Too strict ? '/^(?:r?w\+?|[waxc]\+?|r\+)[ebt]?$/';

    /**
     * @var ?resource
     */
    protected $stream;
    protected bool $isSeekable;
    protected bool $isWritable;
    protected bool $isReadable;

    /**
     * @param resource|bool $stream
     *
     * @throws RuntimeException
     */
    public function __construct($stream)
    {
        if (!is_resource($stream)) {
            throw new RuntimeException('Stream must be a resource');
        }

        $this->stream = $stream;
        $meta = stream_get_meta_data($this->stream);
        $this->isSeekable = $meta['seekable'];
        $this->isWritable = (bool) preg_match(self::REGEX_WRITABLE, $meta['mode']);
        $this->isReadable = (bool) preg_match(self::REGEX_READABLE, $meta['mode']);
    }

    public function __destruct()
    {
        $this->close();
    }

    public function __toString(): string
    {
        try {
            if ($this->isSeekable()) {
                $this->rewind();
            }
            return $this->getContents();
        } catch (\Throwable) {
            return '';
        }
    }

    public function close(): void
    {
        if ($this->stream) {
            if (is_resource($this->stream)) {
                fclose($this->stream);
            }

            $this->detach();
        }
    }

    public function detach(): mixed
    {
        $stream = $this->stream;
        $this->stream = null;
        $this->isSeekable = $this->isWritable = $this->isReadable = false;
        return $stream;
    }

    public function getSize(): ?int
    {
        if ($this->stream === null) {
            return null;
        }

        $stat = fstat($this->stream);
        return ($stat ? $stat['size'] : null);
    }

    public function tell(): int
    {
        $stream = $this->getResource();

        $position = ftell($stream);
        if ($position === false) {
            throw new RuntimeException('Unable to determine stream position');
        }

        return $position;
    }

    public function eof(): bool
    {
        $stream = $this->getResource();

        return feof($stream);
    }

    public function isSeekable(): bool
    {
        return $this->isSeekable;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $stream = $this->getResource();
        $this->errorIfIsSeekable();

        if (fseek($stream, $offset, $whence) !== 0) {
            throw new RuntimeException('Unable to seek in stream');
        }
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        return $this->isWritable;
    }

    public function write(string $string): int
    {
        $stream = $this->getResource();
        $this->errorIfIsNotWritable();

        $bytes = fwrite($stream, $string);
        if ($bytes === false) {
            throw new RuntimeException('Stream not writable');
        }

        return $bytes;
    }

    public function isReadable(): bool
    {
        return $this->isReadable;
    }

    public function read(int $length): string
    {
        $stream = $this->getResource();
        $this->errorIfIsNotReadable();

        if ($length < 0) {
            throw new RuntimeException('Length must be greater than or equal to zero');
        }

        if ($length === 0) {
            return '';
        }

        $content = fread($stream, $length);
        if ($content === false) {
            throw new RuntimeException('Stream not readable');
        }

        return $content;
    }

    public function getContents(): string
    {
        $stream = $this->getResource();

        if (!$this->isReadable) {
            throw new RuntimeException('Stream not readable');
        }

        $content = stream_get_contents($stream);
        if ($content === false) {
            throw new RuntimeException('Stream not readable');
        }

        return $content;
    }

    public function getMetadata(?string $key = null): mixed
    {
        $stream = $this->getResource();

        $meta = stream_get_meta_data($stream);
        if ($key !== null) {
            return $meta[$key] ?? null;
        }

        return $meta;
    }

    /* --- */

    /**
     * @return resource
     * @throws RuntimeException
     */
    protected function getResource()
    {
        if ($this->stream === null) {
            throw new RuntimeException('Stream is detached');
        }
        return $this->stream;
    }

    /**
     * @return void
     * @throws RuntimeException
     */
    protected function errorIfIsDetached(): void
    {
        if ($this->stream === null) {
            throw new RuntimeException('Stream is detached');
        }
    }

    /**
     * @return void
     * @throws RuntimeException
     */
    protected function errorIfIsSeekable(): void
    {
        if (!$this->isSeekable) {
            throw new RuntimeException('Stream not seekable');
        }
    }

    /**
     * @return void
     * @throws RuntimeException
     */
    protected function errorIfIsNotReadable(): void
    {
        if (!$this->isReadable) {
            throw new RuntimeException('Stream not readable');
        }
    }

    /**
     * @return void
     * @throws RuntimeException
     */
    protected function errorIfIsNotWritable(): void
    {
        if (!$this->isWritable) {
            throw new RuntimeException('Stream not writable');
        }
    }
}

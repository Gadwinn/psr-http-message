<?php

declare(strict_types=1);

namespace G\Http\Message;

use Psr\Http\Message\{StreamFactoryInterface, StreamInterface};
use InvalidArgumentException;
use RuntimeException;

/**
 * PSR-17
 * @version 1.0.0
 */
class StreamFactory implements StreamFactoryInterface
{
    protected const REGEX_MODE = '/^[rwaxce]\+?[bt]?$/';

    public function createStream(string $content = ''): StreamInterface
    {
        $stream = self::createStreamFromFile('php://temp', 'r+');
        $stream->write($content);
        $stream->rewind();
        return $stream;
    }

    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        if (!preg_match(self::REGEX_MODE, $mode)) {
            throw new InvalidArgumentException('mode must be a valid mode');
        }

        if ($filename === '' || (stripos($filename, 'php://') !== 0 && !file_exists($filename))) {
            throw new RuntimeException('filename must be a valid path');
        }

        $file = fopen($filename, $mode);
        if ($file === false) {
            throw new RuntimeException('filename must be a valid path');
        }

        return new Stream($file);
    }

    public function createStreamFromResource($resource): StreamInterface
    {
        $stream = new Stream($resource);
        if (!$stream->isReadable()) {
            throw new RuntimeException('Resource must be readable');
        }
        return $stream;
    }
}

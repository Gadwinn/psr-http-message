<?php

declare(strict_types=1);

namespace G\Http\Message\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use G\Http\Message\StreamFactory;
use InvalidArgumentException;
use RuntimeException;

final class StreamFactoryTest extends TestCase
{
    /**
     * @var StreamFactory
     */
    private $StreamFactory;

    protected function setUp(): void
    {
        $this->StreamFactory = new StreamFactory();
    }

    public function testCreateStreamReturnsStreamInstance(): void
    {
        $Stream = $this->StreamFactory->createStream('Content text');
        $this->assertInstanceOf(StreamInterface::class, $Stream);
    }

    public function testCreateStreamFromFileReturnsStreamInstance(): void
    {
        $Stream = $this->StreamFactory->createStreamFromFile('php://memory', 'r+');
        $this->assertInstanceOf(StreamInterface::class, $Stream);
    }

    public function testCreateStreamFromFileInvalidFileThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        
        @$this->StreamFactory->createStreamFromFile('invalid_file', 'r+');
    }

    public function testCreateStreamFromResourceReturnsStreamInstance(): void
    {
        $Stream = $this->StreamFactory->createStreamFromResource(fopen('php://memory', 'r+'));
        $this->assertInstanceOf(StreamInterface::class, $Stream);
    }

    public function testCreateStreamFromResourceInvalidResourceThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        
        $this->StreamFactory->createStreamFromResource('invalid_resource');
    }
}

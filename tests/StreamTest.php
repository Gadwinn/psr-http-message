<?php

declare(strict_types=1);

namespace G\Http\Message\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use G\Http\Message\Stream;

final class StreamTest extends TestCase
{
    /**
     * @var array with functionName => reason
     */
    protected $skippedTests = [];

    /**
     * @param string|resource|StreamInterface $data
     *
     * @return StreamInterface
     */
    public function createStream($data)
    {
        return new Stream($data);
    }

    public function testToStringReadOnlyStreams()
    {
        $resource = fopen(__FILE__, 'r');
        $stream = $this->createStream($resource);

        // Make sure this does not throw exception
        $content = (string) $stream;
        $this->assertNotEmpty($content, 'You MUST be able to convert a read only stream to string');
    }

    public function testToStringRewindStreamBeforeToString()
    {
        $resource = fopen('php://memory', 'rw');
        fwrite($resource, 'abcdef');
        fseek($resource, 3);
        $stream = $this->createStream($resource);

        $content = (string) $stream;
        $this->assertEquals('abcdef', $content);
    }

    public function testClose()
    {
        $resource = fopen('php://memory', 'rw');
        fwrite($resource, 'abcdef');
        $stream = $this->createStream($resource);

        $this->assertTrue(is_resource($resource));
        $stream->close();
        $this->assertFalse(is_resource($resource));
    }

    public function testDetach()
    {
        $resource = fopen('php://memory', 'rw');
        fwrite($resource, 'abc');
        $stream = $this->createStream($resource);

        $this->assertEquals($resource, $stream->detach());
    }

    public function testGetSize()
    {
        $resource = fopen('php://memory', 'rw');
        fwrite($resource, 'abc');
        $stream = $this->createStream($resource);

        $this->assertEquals(3, $stream->getSize());
    }

    public function testTell()
    {
        $resource = fopen('php://memory', 'rw');
        fwrite($resource, 'abcdef');
        $stream = $this->createStream($resource);

        $this->assertSame(6, $stream->tell());
        $stream->seek(0);
        $this->assertSame(0, $stream->tell());
        $stream->seek(3);
        $this->assertSame(3, $stream->tell());
        $stream->seek(6);
        $this->assertSame(6, $stream->tell());
    }

    public function testEof()
    {
        $resource = fopen('php://memory', 'rw');
        fwrite($resource, 'abcdef');
        $stream = $this->createStream($resource);

        $stream->seek(0);
        $this->assertFalse($stream->eof());
        $stream->read(20);
        $stream->read(10);
        $this->assertTrue($stream->eof());
    }

    public function testIsSeekable()
    {
        $resource = fopen('php://memory', 'rw');
        fwrite($resource, 'abcdef');
        $stream = $this->createStream($resource);
        $this->assertTrue($stream->isSeekable());
    }

    /**
     * @group internet
     */
    public function testIsNotSeekable()
    {
        $url = 'https://raw.githubusercontent.com/php-http/multipart-stream-builder/master/tests/Resources/httplug.png';
        $resource = fopen($url, 'r');
        $stream = $this->createStream($resource);
        $this->assertFalse($stream->isSeekable());
    }

    public function testIsWritable()
    {
        $resource = fopen('php://memory', 'rw');
        fwrite($resource, 'abcdef');
        $stream = $this->createStream($resource);
        $this->assertTrue($stream->isWritable());
    }

    /**
     * @group internet
     */
    public function testIsNotWritable()
    {
        $url = 'https://raw.githubusercontent.com/php-http/multipart-stream-builder/master/tests/Resources/httplug.png';
        $resource = fopen($url, 'r');
        $stream = $this->createStream($resource);
        $this->assertFalse($stream->isWritable());
    }

    public function testIsReadable()
    {
        $resource = fopen('php://memory', 'rw');
        fwrite($resource, 'abcdef');
        $stream = $this->createStream($resource);
        $this->assertTrue($stream->isReadable());
    }

    /**
     * @group internet
     */
    public function testIsNotReadable()
    {
        $url = 'https://raw.githubusercontent.com/php-http/multipart-stream-builder/master/tests/Resources/httplug.png';
        $resource = fopen($url, 'r');
        $stream = $this->createStream($resource);
        $this->assertTrue($stream->isReadable());
    }

    public function testSeek()
    {
        $resource = fopen('php://memory', 'rw');
        fwrite($resource, 'abcdef');
        $stream = $this->createStream($resource);
        $stream->seek(3);

        $this->assertEquals('def', fread($resource, 3));
    }

    public function testRewind()
    {
        $resource = fopen('php://memory', 'rw');
        fwrite($resource, 'abcdef');
        $stream = $this->createStream($resource);
        $stream->rewind();

        $this->assertEquals('abcdef', fread($resource, 6));
    }

    /**
     * @group internet
     */
    public function testRewindNotSeekable()
    {
        $this->expectException(\RuntimeException::class);

        $url = 'https://raw.githubusercontent.com/php-http/multipart-stream-builder/master/tests/Resources/httplug.png';
        $resource = fopen($url, 'r');
        $stream = $this->createStream($resource);
        $stream->rewind();
    }

    public function testWrite()
    {
        $resource = fopen('php://memory', 'rw');
        fwrite($resource, 'abc');
        $stream = $this->createStream($resource);
        $bytes = $stream->write('def');

        $this->assertSame(3, $bytes);
        $this->assertEquals('abcdef', (string) $stream);
    }

    public function testRead()
    {
        $resource = fopen('php://memory', 'rw');
        fwrite($resource, 'abcdef');
        $stream = $this->createStream($resource);
        $stream->rewind();

        $data = $stream->read(3);
        $this->assertEquals('abc', $data);

        $data = $stream->read(10);
        $this->assertEquals('def', $data);
    }

    public function testGetContents()
    {
        $resource = fopen('php://memory', 'rw');
        fwrite($resource, 'abcdef');
        $stream = $this->createStream($resource);
        $stream->rewind();

        $stream->seek(3);
        $this->assertEquals('def', $stream->getContents());
        $this->assertSame('', $stream->getContents());
    }

    public function testReadZeroDoesNotConsumeTheStream(): void
    {
        $resource = fopen('php://memory', 'rw');
        fwrite($resource, 'abcdef');
        rewind($resource);
        $stream = $this->createStream($resource);

        $this->assertSame('', $stream->read(0));
        $this->assertSame(0, $stream->tell());
        $this->assertSame('abc', $stream->read(3));
    }

    public function testSeekFailureThrowsException(): void
    {
        $stream = $this->createStream(fopen('php://memory', 'rw'));

        $this->expectException(\RuntimeException::class);

        $stream->seek(-1);
    }

    public function testGetSizeIsNullOnceDetached(): void
    {
        $resource = fopen('php://memory', 'rw');
        $stream = $this->createStream($resource);
        $stream->detach();

        $this->assertNull($stream->getSize());
        fclose($resource);
    }
}

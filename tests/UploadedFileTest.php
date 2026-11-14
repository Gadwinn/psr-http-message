<?php

declare(strict_types=1);

namespace G\Http\Message\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{StreamInterface, UploadedFileInterface};
use G\Http\Message\UploadedFile;

final class UploadedFileTest extends TestCase
{
    /**
     * @var array with functionName => reason
     */
    protected $skippedTests = [];

    private string $filename = 'UploadedFileTest.txt';
    /**
     * @return UploadedFileInterface that is used in the tests
     */
    public function createFile()
    {
        $file = $this->filename;
        file_put_contents($file, 'test');
        return new UploadedFile($file);
    }

    /**
     * @return UploadedFileInterface that is used in the tests
     */
    public function createStream()
    {
        $file = fopen('php://memory', 'rw+');
        fwrite($file, 'test');
        return new UploadedFile($file);
    }

    public function testGetStream()
    {
        $file = $this->createStream();
        $stream = $file->getStream();
        $this->assertTrue($stream instanceof StreamInterface);
    }

    public function testGetStreamFile()
    {
        $file = $this->createFile();
        $stream = $file->getStream();
        $this->assertTrue($stream instanceof StreamInterface);
    }

    public function testGetStreamAfterMoveTo()
    {
        $file = $this->createStream();
        $file->moveTo(sys_get_temp_dir() . '/' . uniqid('foo', true));
        $this->expectException(\RuntimeException::class);
        $file->getStream();
    }

    public function testGetStreamAfterMoveToFile()
    {
        $file = $this->createFile();
        $file->moveTo(sys_get_temp_dir() . '/' . uniqid('foo', true));
        $this->expectException(\RuntimeException::class);
        $file->getStream();
    }

    public function testMoveToAbsolute()
    {
        $file = $this->createStream();
        $targetPath = sys_get_temp_dir() . '/' . uniqid('foo', true);

        $this->assertFalse(is_file($targetPath));
        $file->moveTo($targetPath);
        $this->assertTrue(is_file($targetPath));
    }

    public function testMoveToAbsoluteFile()
    {
        $file = $this->createFile();
        $targetPath = sys_get_temp_dir() . '/' . uniqid('foo', true);

        $this->assertFalse(is_file($targetPath));
        $file->moveTo($targetPath);
        $this->assertTrue(is_file($targetPath));
    }

    public function testMoveToRelative()
    {
        $file = $this->createStream();
        $targetPath = sys_get_temp_dir() . '/' . uniqid('foo', true);

        $this->assertFalse(is_file($targetPath));
        $file->moveTo($targetPath);
        $this->assertTrue(is_file($targetPath));
    }

    public function testMoveToRelativeFile()
    {
        $file = $this->createFile();
        $targetPath = sys_get_temp_dir() . '/' . uniqid('foo', true);

        $this->assertFalse(is_file($targetPath));
        $file->moveTo($targetPath);
        $this->assertTrue(is_file($targetPath));
    }

    public function testMoveToTwice()
    {
        $file = $this->createStream();
        $file->moveTo(sys_get_temp_dir() . '/' . uniqid('foo', true));
        $this->expectException(\RuntimeException::class);
        $file->moveTo(sys_get_temp_dir() . '/' . uniqid('foo', true));
    }

    public function testMoveToTwiceFile()
    {
        $file = $this->createFile();
        $file->moveTo(sys_get_temp_dir() . '/' . uniqid('foo', true));
        $this->expectException(\RuntimeException::class);
        $file->moveTo(sys_get_temp_dir() . '/' . uniqid('foo', true));
    }

    public function testGetSize()
    {
        $file = $this->createStream();
        $size = $file->getSize();
        if ($size) {
            $this->assertMatchesRegularExpression('|^[0-9]+$|', (string) $size);
        } else {
            $this->assertNull($size);
        }
    }

    public function testGetSizeFile()
    {
        $file = $this->createFile();
        $size = $file->getSize();
        if ($size) {
            $this->assertMatchesRegularExpression('|^[0-9]+$|', (string) $size);
        } else {
            $this->assertNull($size);
        }
    }

    public function testGetError()
    {
        $file = $this->createStream();
        $this->assertEquals(UPLOAD_ERR_OK, $file->getError());
    }

    public function testGetErrorFile()
    {
        $file = $this->createFile();
        $this->assertEquals(UPLOAD_ERR_OK, $file->getError());
    }

    public function testGetClientFilename()
    {
        $file = $this->createStream();
        $name = $file->getClientFilename();
        if ($name) {
            $this->assertTrue(is_string($name));
        } else {
            $this->assertNull($name);
        }
    }

    public function testGetClientFilenameFile()
    {
        $file = $this->createFile();
        $name = $file->getClientFilename();
        if ($name) {
            $this->assertTrue(is_string($name));
        } else {
            $this->assertNull($name);
        }
    }

    public function testGetClientMediaType()
    {
        $file = $this->createStream();
        $type = $file->getClientMediaType();
        if ($type) {
            $this->assertTrue(is_string($type));
        } else {
            $this->assertNull($type);
        }
    }

    public function testGetClientMediaTypeFile()
    {
        $file = $this->createFile();
        $type = $file->getClientMediaType();
        if ($type) {
            $this->assertTrue(is_string($type));
        } else {
            $this->assertNull($type);
        }
    }

    public function testDeleteFile()
    {
        $this->assertTrue(file_exists($this->filename) ? unlink($this->filename) : true);
    }

    public function testGetStreamOfFileIsReadable(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'upl');
        file_put_contents($path, 'content');

        $stream = (new UploadedFile($path))->getStream();

        $this->assertSame('content', (string) $stream);
        $stream->close();
        unlink($path);
    }

    public function testGetStreamOfMissingFileThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);

        (new UploadedFile(__DIR__ . '/does-not-exist.tmp'))->getStream();
    }
}

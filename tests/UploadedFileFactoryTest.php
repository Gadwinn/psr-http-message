<?php

declare(strict_types=1);

namespace G\Http\Message\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UploadedFileInterface;
use G\Http\Message\{StreamFactory, UploadedFileFactory};
use InvalidArgumentException;

final class UploadedFileFactoryTest extends TestCase
{
    /**
     * @var UploadedFileFactory
     */
    private $UploadedFileFactory;

    protected function setUp(): void
    {
        $this->UploadedFileFactory = new UploadedFileFactory();
    }

    public function testCreateUploadedFileReturnsUploadedFileInstance(): void
    {
        $UploadedFile = $this->UploadedFileFactory->createUploadedFile(
            (new StreamFactory())->createStream('Content text'),
            0,
            UPLOAD_ERR_OK,
            'filename.txt',
            'text/plain'
        );
        $this->assertInstanceOf(UploadedFileInterface::class, $UploadedFile);
    }

    public function testCreateUploadedFileInvalidResourceReadableThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->UploadedFileFactory->createUploadedFile(
            (new StreamFactory())->createStreamFromFile(__FILE__, 'a'),
            0,
            UPLOAD_ERR_OK,
            'filename.txt',
            'text/plain'
        );
    }
}

<?php

declare(strict_types=1);

namespace G\Http\Message;

use Psr\Http\Message\{UploadedFileInterface, StreamInterface};
use InvalidArgumentException;
use RuntimeException;

/**
 * PSR-7
 * @version 1.0.0
 */
class UploadedFile implements UploadedFileInterface
{
    protected const ERROR_UNKNOWN = 'Unknown error';

    protected const ERRORS = [
        UPLOAD_ERR_OK => 'There is no error, the file uploaded with success',
        UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE'
            . ' directive that was specified in the HTML form',
        UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
    ];

    protected string $file = '';
    protected ?StreamInterface $stream = null;
    protected ?int $size;
    protected int $error;
    protected ?string $clientFilename;
    protected ?string $clientMediaType;
    protected bool $moved = false;

    /**
     * @param StreamInterface|resource|string $streamOrFile
     * @param int|null $size
     * @param int $error
     * @param string|null $clientFilename
     * @param string|null $clientMediaType
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        $streamOrFile,
        ?int $size = null,
        int $error = UPLOAD_ERR_OK,
        ?string $clientFilename = null,
        ?string $clientMediaType = null
    ) {
        if ($error === UPLOAD_ERR_OK) {
            if (is_string($streamOrFile)) {
                $this->file = $streamOrFile;
            } elseif (is_resource($streamOrFile)) {
                $this->stream = new Stream($streamOrFile);
            } elseif ($streamOrFile instanceof StreamInterface) {
                $this->stream = $streamOrFile;
            } else {
                throw new InvalidArgumentException('Invalid stream');
            }
        }

        $this->size = $size;

        if (!isset(self::ERRORS[$error])) {
            throw new InvalidArgumentException('Invalid error');
        }

        $this->error = $error;
        $this->clientFilename = $clientFilename;
        $this->clientMediaType = $clientMediaType;
    }

    public function getStream(): StreamInterface
    {
        if ($this->moved) {
            throw new RuntimeException('Stream already moved');
        }

        if ($this->error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Stream cannot be found');
        }

        if ($this->stream instanceof StreamInterface) {
            return $this->stream;
        }

        if ($this->file === '') {
            throw new RuntimeException('File cannot be found');
        }

        // Read-only: an uploaded temporary file is not necessarily writable.
        return new Stream(@fopen($this->file, 'r'));
    }

    public function moveTo($targetPath): void
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            throw new RuntimeException(self::ERRORS[$this->error] ?? self::ERROR_UNKNOWN);
        }

        if ($this->moved) {
            throw new RuntimeException('Stream already moved');
        }

        if (!is_string($targetPath) || $targetPath === '') {
            throw new InvalidArgumentException('Invalid targetPath');
        }

        if ($this->file !== '') {
            $this->moved = (PHP_SAPI === 'cli' ?
                rename($this->file, $targetPath) :
                move_uploaded_file($this->file, $targetPath)
            );
        } else {
            $target = new Stream(fopen($targetPath, 'w'));
            $this->getStream()->rewind();
            $target->write($this->getStream()->getContents());
            $this->moved = true;
        }

        if ($this->moved === false) {
            throw new RuntimeException('Failed move');
        }
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

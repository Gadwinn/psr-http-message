<?php

declare(strict_types=1);

namespace G\Http\Message;

use Psr\Http\Message\{ServerRequestInterface, StreamInterface, UploadedFileInterface, UriInterface};
use InvalidArgumentException;

/**
 * PSR-7
 * @version 1.0.0
 */
class ServerRequest extends Request implements ServerRequestInterface
{
    /**
     * @var array<array-key,mixed>
     */
    protected array $server;
    /**
     * @var array<array-key,mixed>
     */
    protected array $cookie;
    /**
     * @var array<array-key,mixed>
     */
    protected array $query;
    /**
     * @var array<string,mixed>
     */
    protected array $uploadedFiles;
    /**
     * @var object|array<array-key,mixed>|null
     */
    protected $data;
    /**
     * @var array<string,mixed>
     */
    protected array $attributes = [];

    /**
     * @param string $protocolVersion
     * @param array<array-key,mixed> $headers
     * @param StreamInterface|resource|null $body
     * @param string $requestTarget
     * @param string $method
     * @param UriInterface|string $uri
     * @param array<array-key,mixed> $serverParams
     * @param array<array-key,mixed> $cookieParams
     * @param array<array-key,mixed> $queryParams
     * @param array<string,mixed> $uploadedFiles
     * @param object|array<array-key,mixed>|null $parsedBody
     * @param array<string,mixed> $attributes
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        string $protocolVersion = '1.1',
        array $headers = [],
        $body = null,
        string $requestTarget = '',
        string $method = 'GET',
        $uri = '/',
        array $serverParams = [],
        array $cookieParams = [],
        array $queryParams = [],
        array $uploadedFiles = [],
        $parsedBody = null,
        array $attributes = []
    ) {
        parent::__construct($protocolVersion, $headers, $body, $requestTarget, $method, $uri);

        $this->setServerParams($serverParams);
        $this->setCookieParams($cookieParams);
        $this->setQueryParams($queryParams);
        $this->setUploadedFiles($uploadedFiles);
        $this->setParsedBody($parsedBody);
        if ($attributes !== []) {
            foreach ($attributes as $name => $value) {
                $this->setAttributes($name, $value);
            }
        }
    }

    /**
     * @return array<array-key,mixed>
     */
    public function getServerParams(): array
    {
        return $this->server;
    }

    /**
     * @return array<array-key,mixed>
     */
    public function getCookieParams(): array
    {
        return $this->cookie;
    }

    /**
     * @param array<array-key,mixed> $cookie Array of key/value pairs representing cookies.
     */
    public function withCookieParams(array $cookie): ServerRequestInterface
    {
        $serverRequest = clone $this;
        $serverRequest->setCookieParams($cookie);
        return $serverRequest;
    }

    /**
     * @return array<array-key,mixed>
     */
    public function getQueryParams(): array
    {
        return $this->query;
    }

    /**
     * @param array<array-key,mixed> $query Array of query string arguments, typically from
     *     $_GET.
     */
    public function withQueryParams(array $query): ServerRequestInterface
    {
        $serverRequest = clone $this;
        $serverRequest->setQueryParams($query);
        return $serverRequest;
    }

    /**
     * @return array<string,mixed> An array tree of UploadedFileInterface instances; an empty
     *     array MUST be returned if no data is present.
     */
    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    /**
     * @param array<string,mixed> $uploadedFiles An array tree of UploadedFileInterface instances.
     */
    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
    {
        $serverRequest = clone $this;
        $serverRequest->setUploadedFiles($uploadedFiles);
        return $serverRequest;
    }

    /**
     * @return object|array<array-key,mixed>|null The deserialized body parameters, if any.
     *     These will typically be an array or object.
     */
    public function getParsedBody()
    {
        return $this->data;
    }

    /**
     * @param object|array<array-key,mixed>|null $data $data The deserialized body data. This will
     *     typically be in an array or object.
     */
    public function withParsedBody($data): ServerRequestInterface
    {
        $serverRequest = clone $this;
        $serverRequest->setParsedBody($data);
        return $serverRequest;
    }

    /**
     * @return array<string,mixed> The attributes derived from the request.
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute($name, $default = null): mixed
    {
        return array_key_exists($name, $this->attributes) ? $this->attributes[$name] : $default;
    }

    public function withAttribute($name, $value): ServerRequestInterface
    {
        $serverRequest = clone $this;
        $serverRequest->setAttributes($name, $value);
        return $serverRequest;
    }

    public function withoutAttribute($name): ServerRequestInterface
    {
        $serverRequest = clone $this;
        unset($serverRequest->attributes[$name]);
        return $serverRequest;
    }

    /* --- */

    /**
     * @param array<array-key,mixed> $server
     *
     * @return ServerRequest
     */
    protected function setServerParams($server): self
    {
        $this->server = $server;
        return $this;
    }

    /**
     * @param array<array-key,mixed> $cookie
     *
     * @return ServerRequest
     */
    protected function setCookieParams($cookie): self
    {
        $this->cookie = $cookie;
        return $this;
    }

    /**
     * @param array<array-key,mixed> $query
     *
     * @return ServerRequest
     */
    protected function setQueryParams($query): self
    {
        $this->query = $query;
        return $this;
    }

    /**
     * @param array<string,mixed> $uploadedFiles
     *
     * @return ServerRequest
     * @throws InvalidArgumentException
     */
    protected function setUploadedFiles(array $uploadedFiles): self
    {
        $this->validateUploadedFiles($uploadedFiles);
        $this->uploadedFiles = $uploadedFiles;
        return $this;
    }

    /**
     * @param array<array-key,mixed> $uploadedFiles
     *
     * @return void
     * @throws InvalidArgumentException
     */
    protected function validateUploadedFiles(array $uploadedFiles): void
    {
        foreach ($uploadedFiles as $uploadedFile) {
            if (is_array($uploadedFile)) {
                $this->validateUploadedFiles($uploadedFile);
                continue;
            }

            if (!$uploadedFile instanceof UploadedFileInterface) {
                throw new InvalidArgumentException('Invalid uploaded files');
            }
        }
    }

    /**
     * @param array<array-key,mixed> $files
     *
     * @return array<string,mixed>
     */
    public static function normalizeFiles(array $files): array
    {
        /**
         * @param array{name:mixed,type:mixed,tmp_name:mixed,error:mixed,size:mixed} $file
         * @return UploadedFile|array<string,mixed>
         */
        $_normalizeFilesRecursive = static function (array $file) use (&$_normalizeFilesRecursive): UploadedFile|array {
            $tmpName = $file['tmp_name'];
            if (is_array($tmpName)) {
                $names = is_array($file['name']) ? $file['name'] : [];
                $types = is_array($file['type']) ? $file['type'] : [];
                $errors = is_array($file['error']) ? $file['error'] : [];
                $sizes = is_array($file['size']) ? $file['size'] : [];
                $recursiveFiles = [];
                foreach (array_keys($tmpName) as $key) {
                    /** @var array{name:mixed,type:mixed,tmp_name:mixed,error:mixed,size:mixed} $entry */
                    $entry = [
                        'name'     => $names[$key] ?? null,
                        'type'     => $types[$key] ?? null,
                        'tmp_name' => $tmpName[$key],
                        'error'    => $errors[$key] ?? UPLOAD_ERR_NO_FILE,
                        'size'     => $sizes[$key] ?? null,
                    ];
                    $recursiveFiles[(string) $key] = $_normalizeFilesRecursive($entry);
                }
                return $recursiveFiles;
            }

            return new UploadedFile(
                is_string($tmpName) ? $tmpName : '',
                is_int($file['size']) ? $file['size'] : (is_numeric($file['size']) ? (int) $file['size'] : null),
                is_int($file['error'])
                    ? $file['error']
                    : (is_scalar($file['error']) || $file['error'] === null
                        ? (int) $file['error']
                        : UPLOAD_ERR_NO_FILE),
                is_string($file['name']) ? $file['name'] : null,
                is_string($file['type']) ? $file['type'] : null,
            );
        };

        $normalizedFiles = [];
        foreach ($files as $name => $file) {
            if (is_array($file)) {
                $normalizedFiles[(string) $name] = $_normalizeFilesRecursive($file);
            }
        }
        return $normalizedFiles;
    }

    /**
     * @param object|array<array-key,mixed>|null $data
     *
     * @return ServerRequest
     * @throws InvalidArgumentException
     */
    protected function setParsedBody($data): self
    {
        $this->validateParsedBody($data);
        $this->data = $data;
        return $this;
    }

    /**
     * @param object|array<array-key,mixed>|null $data
     *
     * @return void
     * @throws InvalidArgumentException
     */
    protected function validateParsedBody($data): void
    {
        if (!is_object($data) && !is_array($data) && $data !== null) {
            throw new InvalidArgumentException('Invalid parsed body');
        }
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return ServerRequest
     */
    protected function setAttributes($name, $value): self
    {
        $this->attributes[$name] = $value;
        return $this;
    }
}

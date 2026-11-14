<?php

declare(strict_types=1);

namespace G\Http\Message;

use Psr\Http\Message\{MessageInterface, StreamInterface};
use InvalidArgumentException;

/**
 * PSR-7
 * @version 1.0.0
 */
class Message implements MessageInterface
{
    protected string $protocolVersion;
    /**
     * @var array<string,array<string>>
     */
    protected array $headers = [];
    /**
     * @var array<string,array<string>>
     */
    protected array $headersNormalized = [];
    protected ?StreamInterface $body = null;

    // The "D" modifier is required: without it "$" also matches before a trailing line feed,
    // which would let "value\n" through (header injection).
    protected const REGEX_HEADER_NAME = '/^[\w\'`#$%&*+.^|~!-]+$/D';
    protected const REGEX_HEADER_VALUE = '/^[^\0\r\n]+$/D';

    public const METHOD_GET = 'GET';
    public const METHOD_POST = 'POST';
    public const METHOD_PUT = 'PUT';
    public const METHOD_PATCH = 'PATCH';
    public const METHOD_DELETE = 'DELETE';
    public const METHOD_CONNECT = 'CONNECT';
    public const METHOD_HEAD = 'HEAD';
    public const METHOD_OPTIONS = 'OPTIONS';
    public const METHOD_TRACE = 'TRACE';

    /**
     * @param string $protocolVersion
     * @param array<array-key,mixed> $headers
     * @param StreamInterface|resource|null $body
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function __construct(
        string $protocolVersion = '1.1',
        array $headers = [],
        $body = null
    ) {
        $this->setProtocolVersion($protocolVersion);

        if ($headers !== []) {
            foreach ($headers as $name => $value) {
                if (is_string($name) && (is_string($value) || is_array($value))) {
                    $this->setHeader($name, $value);
                }
            }
        }

        if ($body !== null) {
            $this->setBody($body instanceof StreamInterface ? $body : new Stream($body));
        }
    }

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion($version): MessageInterface
    {
        $message = clone $this;
        $message->setProtocolVersion($version);
        return $message;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader($name): bool
    {
        return isset($this->headersNormalized[strtolower($name)]);
    }

    public function getHeader($name): array
    {
        return $this->headersNormalized[strtolower($name)] ?? [];
    }

    public function getHeaderLine($name): string
    {
        return implode(',', $this->getHeader($name));
    }

    public function withHeader($name, $value): MessageInterface
    {
        $message = clone $this;
        $message->setHeader($name, $value);
        return $message;
    }

    public function withAddedHeader($name, $value): MessageInterface
    {
        $message = clone $this;
        $message->setAddedHeader($name, $value);
        return $message;
    }

    public function withoutHeader($name): MessageInterface
    {
        $message = clone $this;
        $message->unsetHeader($name);
        return $message;
    }

    public function getBody(): StreamInterface
    {
        if ($this->body === null) {
            $this->body = new Stream(fopen('php://temp', 'r+'));
        }

        return $this->body;
    }

    public function withBody(StreamInterface $body): MessageInterface
    {
        $message = clone $this;
        $message->setBody($body);
        return $message;
    }

    /* --- */

    /**
     * @param string $version
     *
     * @return Message
     */
    protected function setProtocolVersion(string $version): self
    {
        $this->protocolVersion = $version;
        return $this;
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return Message
     * @throws InvalidArgumentException
     */
    protected function setHeader($name, $value): self
    {
        if (!is_string($name) || !preg_match(self::REGEX_HEADER_NAME, $name)) {
            throw new InvalidArgumentException('Invalid name');
        }
        return $this
            ->unsetHeader($name)
            ->setAddedHeader($name, $value);
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return Message
     * @throws InvalidArgumentException
     */
    protected function setAddedHeader($name, $value): self
    {
        if (!is_string($name) || !preg_match(self::REGEX_HEADER_NAME, $name)) {
            throw new InvalidArgumentException('Invalid name');
        } elseif (!is_array($value) && !is_string($value) || $value === [] || $value === '') {
            throw new InvalidArgumentException('Invalid value');
        }
        foreach ((array) $value as $val) {
            if (!is_string($val) || trim($val) === '' || !preg_match(self::REGEX_HEADER_VALUE, $val)) {
                throw new InvalidArgumentException('Invalid value');
            }
            $this->headers[$name][] = $val;
            $this->headersNormalized[strtolower($name)][] = $val;
        }
        return $this;
    }

    /**
     * @param string $name
     *
     * @return Message
     */
    protected function unsetHeader(string $name): self
    {
        if ($this->hasHeader($name)) {
            foreach (array_keys($this->headers) as $name_header) {
                if (strtolower($name) === strtolower($name_header)) {
                    unset($this->headers[$name_header]);
                }
            }
            unset($this->headersNormalized[strtolower($name)]);
        }
        return $this;
    }

    /**
     * @param StreamInterface $body
     *
     * @return Message
     */
    protected function setBody(StreamInterface $body): self
    {
        $this->body = $body;
        return $this;
    }
}

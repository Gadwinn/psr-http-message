<?php

declare(strict_types=1);

namespace G\Http\Message;

use Psr\Http\Message\{RequestInterface, StreamInterface, UriInterface};
use InvalidArgumentException;

/**
 * PSR-7
 * @version 1.0.0
 */
class Request extends Message implements RequestInterface
{
    protected string $requestTarget;
    protected string $method;
    protected UriInterface $uri;

    /**
     * @param string $protocolVersion
     * @param array<array-key,mixed> $headers
     * @param StreamInterface|resource|null $body
     * @param string $requestTarget
     * @param string $method
     * @param UriInterface|string $uri
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        string $protocolVersion = '1.1',
        array $headers = [],
        $body = null,
        string $requestTarget = '',
        string $method = 'GET',
        $uri = '/'
    ) {
        parent::__construct($protocolVersion, $headers, $body);

        $this->setRequestTarget($requestTarget);
        $this->setMethod($method);

        if (!$uri instanceof UriInterface) {
            if (!is_string($uri)) {
                throw new InvalidArgumentException('Invalid uri');
            }

            $uri = new Uri($uri);
        }
        $this->setUri($uri);
    }

    public function getRequestTarget(): string
    {
        if ($this->requestTarget !== '') {
            return $this->requestTarget;
        }

        $target = ($this->uri->getPath() ?: '/');
        if ($this->uri->getQuery() !== '') {
            $target .= '?' . $this->uri->getQuery();
        }
        return $target;
    }

    public function withRequestTarget($requestTarget): RequestInterface
    {
        $request = clone $this;
        $request->setRequestTarget($requestTarget);
        return $request;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod($method): RequestInterface
    {
        $request = clone $this;
        $request->setMethod($method);
        return $request;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, $preserveHost = false): RequestInterface
    {
        $request = clone $this;
        $request->setUri($uri, $preserveHost);
        return $request;
    }

    /* --- */

    /**
     * @param string $requestTarget
     *
     * @return Request
     */
    protected function setRequestTarget($requestTarget): self
    {
        $this->requestTarget = $requestTarget;
        return $this;
    }

    /**
     * @param string $method
     *
     * @return Request
     * @throws InvalidArgumentException
     */
    protected function setMethod($method): self
    {
        if (!is_string($method) || $method === '') {
            throw new InvalidArgumentException('Invalid method');
        }
        $this->method = $method;
        return $this;
    }

    /**
     * @param UriInterface $uri
     * @param bool $preserveHost
     *
     * @return Request
     */
    protected function setUri(UriInterface $uri, $preserveHost = false): self
    {
        $this->uri = $uri;

        if (!$preserveHost || !isset($this->headersNormalized['host'])) {
            $host = $uri->getHost();
            if (!$host) {
                return $this;
            }

            $port = $uri->getPort();
            if ($port !== null) {
                $host .= ':' . $port;
            }

            $this->unsetHeader('Host');
            $this->headers = ['Host' => [$host]] + $this->headers;
            $this->headersNormalized = ['host' => [$host]] + $this->headersNormalized;
        }
        return $this;
    }
}

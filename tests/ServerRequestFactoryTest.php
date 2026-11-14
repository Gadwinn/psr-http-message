<?php

declare(strict_types=1);

namespace G\Http\Message\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use G\Http\Message\{ServerRequestFactory, Stream};
use InvalidArgumentException;

final class ServerRequestFactoryTest extends TestCase
{
    /**
     * @var ServerRequestFactory
     */
    private $serverRequestFactory;

    protected function setUp(): void
    {
        $this->serverRequestFactory = new ServerRequestFactory();
    }

    public function testCreateServerRequest(): void
    {
        $serverRequest = $this->serverRequestFactory->createServerRequest('GET', '/');

        $this->assertInstanceOf(ServerRequestInterface::class, $serverRequest);
        $this->assertSame('1.1', $serverRequest->getProtocolVersion());
        $this->assertSame([], $serverRequest->getHeaders());
        $this->assertInstanceOf(Stream::class, $serverRequest->getBody());
        $this->assertSame('/', $serverRequest->getRequestTarget());
        $this->assertSame('GET', $serverRequest->getMethod());
        $this->assertSame('/', (string) $serverRequest->getUri());
        $this->assertSame([], $serverRequest->getServerParams());
        $this->assertSame([], $serverRequest->getCookieParams());
        $this->assertSame([], $serverRequest->getQueryParams());
        $this->assertSame([], $serverRequest->getUploadedFiles());
        $this->assertNull($serverRequest->getParsedBody());
        $this->assertSame([], $serverRequest->getAttributes());
    }

    public function testCreateServerRequestWithInvalidUriThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->serverRequestFactory->createServerRequest('GET', []);
    }

    public function testCreateServerRequestFromGlobals(): void
    {
        $serverRequest = ServerRequestFactory::createServerRequestFromGlobals();

        $this->assertInstanceOf(ServerRequestInterface::class, $serverRequest);
        $this->assertSame('1.1', $serverRequest->getProtocolVersion());
        $this->assertSame([], $serverRequest->getHeaders());
        $this->assertInstanceOf(Stream::class, $serverRequest->getBody());
        $this->assertSame('/', $serverRequest->getRequestTarget());
        $this->assertSame('GET', $serverRequest->getMethod());
        $this->assertSame('http:', (string) $serverRequest->getUri());
        $this->assertSame($_SERVER, $serverRequest->getServerParams());
        $this->assertSame($_COOKIE, $serverRequest->getCookieParams());
        $this->assertSame($_GET, $serverRequest->getQueryParams());
        $this->assertSame([], $serverRequest->getUploadedFiles());
        $this->assertSame($_POST ?: null, $serverRequest->getParsedBody());
        $this->assertSame([], $serverRequest->getAttributes());
    }
}

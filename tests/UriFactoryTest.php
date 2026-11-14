<?php

declare(strict_types=1);

namespace G\Http\Message\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;
use G\Http\Message\{UriFactory};

final class UriFactoryTest extends TestCase
{
    /**
     * @var UriFactory
     */
    private $UriFactory;

    protected function setUp(): void
    {
        $this->UriFactory = new UriFactory();
    }

    public function testCreateUri(): void
    {
        $Uri = $this->UriFactory->createUri('http://example.com/');
        $this->assertInstanceOf(UriInterface::class, $Uri);
        $this->assertSame('http://example.com/', (string) $Uri);
    }

    public function testCreateUriFromGlobalsReturnsUriInstance(): void
    {
        $Uri = $this->UriFactory->createUriFromGlobals();
        $this->assertInstanceOf(UriInterface::class, $Uri);
    }

    public function testCreateUriFromGlobalsWithHostServer(): void
    {
        $_SERVER['SERVER_ADDR'] = 'server-addr';
        $Uri = $this->UriFactory->createUriFromGlobals();
        $this->assertSame('server-addr', $Uri->getHost());

        $_SERVER['SERVER_NAME'] = 'server-name';
        $Uri = $this->UriFactory->createUriFromGlobals();
        $this->assertSame('server-name', $Uri->getHost());

        $_SERVER['HTTP_HOST'] = 'http-host';
        $Uri = $this->UriFactory->createUriFromGlobals();
        $this->assertSame('http-host', $Uri->getHost());
    }

    public function testCreateUriFromGlobalsWithPortServer(): void
    {
        $_SERVER['SERVER_PORT'] = '12345';
        $Uri = $this->UriFactory->createUriFromGlobals();
        $this->assertSame(12345, $Uri->getPort());

        $_SERVER['HTTP_HOST'] = 'http-host:23456';
        $Uri = $this->UriFactory->createUriFromGlobals();
        $this->assertSame(23456, $Uri->getPort());
    }

    public function testCreateUriFromGlobalsWithPathServer(): void
    {
        $_SERVER['REQUEST_URI'] = '/path';
        $Uri = $this->UriFactory->createUriFromGlobals();
        $this->assertSame('/path', $Uri->getPath());

        $_SERVER['REQUEST_URI'] = '/path?query';
        $Uri = $this->UriFactory->createUriFromGlobals();
        $this->assertSame('/path', $Uri->getPath());
    }

    public function testCreateUriFromGlobalsWithQueryServer(): void
    {
        $_SERVER['QUERY_STRING'] = 'query-string';
        $Uri = $this->UriFactory->createUriFromGlobals();
        $this->assertSame('query-string', $Uri->getQuery());

        $_SERVER['REQUEST_URI'] = '/path?query';
        $Uri = $this->UriFactory->createUriFromGlobals();
        $this->assertSame('query', $Uri->getQuery());
    }
}

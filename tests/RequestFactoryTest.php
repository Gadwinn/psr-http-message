<?php

declare(strict_types=1);

namespace G\Http\Message\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use G\Http\Message\RequestFactory;

final class RequestFactoryTest extends TestCase
{
    /**
     * @var RequestFactory
     */
    private $requestFactory;

    protected function setUp(): void
    {
        $this->requestFactory = new RequestFactory();
    }

    public function testCreateRequestReturnsRequestInstance(): void
    {
        $request = $this->requestFactory->createRequest('GET', '/');
        $this->assertInstanceOf(RequestInterface::class, $request);
    }

    public function testCreateRequestWithValidArguments(): void
    {
        $method = 'GET';
        $uri = '/';
        $request = $this->requestFactory->createRequest($method, $uri);

        $this->assertEquals($method, $request->getMethod());
        $this->assertEquals($uri, (string) $request->getUri());
    }
}

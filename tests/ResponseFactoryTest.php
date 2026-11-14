<?php

declare(strict_types=1);

namespace G\Http\Message\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use G\Http\Message\ResponseFactory;

final class ResponseFactoryTest extends TestCase
{
    /**
     * @var ResponseFactory
     */
    private ResponseFactory $responseFactory;

    protected function setUp(): void
    {
        $this->responseFactory = new ResponseFactory();
    }

    public function testCreateResponse(): void
    {
        $response = $this->responseFactory->createResponse();
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testCreateResponseWithStatusCode(): void
    {
        $response = $this->responseFactory->createResponse(200);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testCreateResponseWithReasonPhrase(): void
    {
        $response = $this->responseFactory->createResponse(200, 'OK');
        $this->assertSame('OK', $response->getReasonPhrase());
    }

    public function testCreateResponseInvalidStatusCode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->responseFactory->createResponse(600);
    }
}

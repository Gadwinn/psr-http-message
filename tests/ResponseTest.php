<?php

declare(strict_types=1);

namespace G\Http\Message\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use G\Http\Message\Response;

final class ResponseTest extends TestCase
{
    /**
     * @var array with functionName => reason
     */
    protected $skippedTests = [];

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @return ResponseInterface that is used in the tests
     */
    public function createSubject()
    {
        return new Response();
    }

    protected function setUp(): void
    {
        $this->response = $this->createSubject();
    }

    protected function getMessage()
    {
        return $this->response;
    }

    #[DataProvider('getStatusCodeArguments')]
    public function testStatusCode($code)
    {
        $original = clone $this->response;
        $response = $this->response->withStatus($code);
        $this->assertNotSame($this->response, $response);
        $this->assertEquals($this->response, $original, 'Response MUST not be mutated');
        $this->assertSame((int) $code, $response->getStatusCode());
    }

    public static function getStatusCodeArguments()
    {
        return [
            [100],
            [599],
            ['204'],
        ];
    }

    #[DataProvider('getInvalidStatusCodeArguments')]
    public function testStatusCodeInvalidArgument($statusCode)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->response->withStatus($statusCode);
    }

    public static function getInvalidStatusCodeArguments()
    {
        return [
            [true],
            ['foobar'],
            ['500e'],
            [99],
            [600],
            [200.34],
            [new \stdClass()],
        ];
    }

    public function testReasonPhrase()
    {
        $response = $this->response->withStatus(204, 'Foobar');
        $this->assertSame(204, $response->getStatusCode());
        $this->assertEquals('Foobar', $response->getReasonPhrase());
    }
}

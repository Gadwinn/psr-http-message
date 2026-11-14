<?php

declare(strict_types=1);

namespace G\Http\Message\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{RequestInterface, UriInterface};
use G\Http\Message\{Request, Uri};

final class RequestTest extends TestCase
{
    /**
     * @var array with functionName => reason
     */
    protected $skippedTests = [];

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @return RequestInterface that is used in the tests
     */
    public function createSubject()
    {
        return new Request();
    }

    public function buildUri($uri)
    {
        return new Uri($uri);
    }

    protected function setUp(): void
    {
        $this->request = $this->createSubject();
    }

    protected function getMessage()
    {
        return $this->request;
    }

    public function testRequestTarget()
    {
        $original = clone $this->request;
        $this->assertEquals('/', $this->request->getRequestTarget());

        $request = $this->request->withRequestTarget('*');
        $this->assertNotSame($this->request, $request);
        $this->assertEquals($this->request, $original, 'Request object MUST not be mutated');
        $this->assertEquals('*', $request->getRequestTarget());
    }

    public function testMethod()
    {
        $this->assertEquals('GET', $this->request->getMethod());
        $original = clone $this->request;

        $request = $this->request->withMethod('POST');
        $this->assertNotSame($this->request, $request);
        $this->assertEquals($this->request, $original, 'Request object MUST not be mutated');
        $this->assertEquals('POST', $request->getMethod());
    }

    public function testMethodIsCaseSensitive()
    {
        $request = $this->request->withMethod('head');
        $this->assertEquals('head', $request->getMethod());
    }

    public function testMethodIsExtendable()
    {
        $request = $this->request->withMethod('CUSTOM');
        $this->assertEquals('CUSTOM', $request->getMethod());
    }

    #[DataProvider('getInvalidMethods')]
    public function testMethodWithInvalidArguments($method)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->request->withMethod($method);
    }

    public static function getInvalidMethods()
    {
        return [
            [null],
            [1],
            [1.01],
            [false],
            [['foo']],
            [new \stdClass()],
        ];
    }

    public function testUri()
    {
        $original = clone $this->request;

        $this->assertInstanceOf(UriInterface::class, $this->request->getUri());

        $uri = $this->buildUri('http://www.foo.com/bar');
        $request = $this->request->withUri($uri);
        $this->assertNotSame($this->request, $request);
        $this->assertEquals($this->request, $original, 'Request object MUST not be mutated');
        $this->assertEquals('www.foo.com', $request->getHeaderLine('host'));
        $this->assertInstanceOf(UriInterface::class, $request->getUri());
        $this->assertEquals('http://www.foo.com/bar', (string) $request->getUri());

        $request = $request->withUri($this->buildUri('/foobar'));
        $this->assertNotSame($this->request, $request);
        $this->assertEquals($this->request, $original, 'Request object MUST not be mutated');
        $this->assertEquals('www.foo.com', $request->getHeaderLine('host'), 'If the URI does not contain a host component, any pre-existing Host header MUST be carried over to the returned request.');
        $this->assertEquals('/foobar', (string) $request->getUri());
    }

    public function testUriPreserveHost_NoHost_Host()
    {
        $request = $this->request->withUri($this->buildUri('http://www.foo.com/bar'), true);
        $this->assertEquals('www.foo.com', $request->getHeaderLine('host'));
    }

    public function testUriPreserveHost_NoHost_NoHost()
    {
        $host = $this->request->getHeaderLine('host');
        $request = $this->request->withUri($this->buildUri('/bar'), true);
        $this->assertEquals($host, $request->getHeaderLine('host'));
    }

    public function testUriPreserveHost_Host_Host()
    {
        $request = $this->request->withUri($this->buildUri('http://www.foo.com/bar'));
        $host = $request->getHeaderLine('host');

        $request2 = $request->withUri($this->buildUri('http://www.bar.com/foo'), true);
        $this->assertEquals($host, $request2->getHeaderLine('host'));
    }
}

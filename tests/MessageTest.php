<?php

declare(strict_types=1);

namespace G\Http\Message\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\MessageInterface;
use G\Http\Message\{Message, Stream};

final class MessageTest extends TestCase
{
    /**
     * @return MessageInterface
     */
    public function getMessage()
    {
        return new Message();
    }

    public function buildStream($data)
    {
        $resource = fopen('php://memory', 'rw');
        fwrite($resource, $data);
        fseek($resource, 0);
        return new Stream($resource);
    }

    public function testProtocolVersion()
    {
        $initialMessage = $this->getMessage();
        $original = clone $initialMessage;

        $message = $initialMessage->withProtocolVersion('1.0');

        $this->assertNotSame($initialMessage, $message);
        $this->assertEquals($initialMessage, $original, 'Message object MUST not be mutated');

        $this->assertSame('1.0', $message->getProtocolVersion());
    }

    public function testGetHeaders()
    {
        $initialMessage = $this->getMessage();
        $original = clone $initialMessage;

        $message = $initialMessage
            ->withAddedHeader('content-type', 'text/html')
            ->withAddedHeader('content-type', 'text/plain');

        $this->assertEquals($initialMessage, $original, 'Message object MUST not be mutated');

        $headers = $message->getHeaders();

        $this->assertTrue(isset($headers['content-type']));
        $this->assertCount(2, $headers['content-type']);
        $this->assertContains('text/html', $headers['content-type']);
        $this->assertContains('text/plain', $headers['content-type']);
    }

    public function testHasHeader()
    {
        $message = $this->getMessage()->withAddedHeader('content-type', 'text/html');

        $this->assertTrue($message->hasHeader('content-type'));
        $this->assertTrue($message->hasHeader('Content-Type'));
        $this->assertTrue($message->hasHeader('ConTent-Type'));
    }

    public function testGetHeader()
    {
        $message = $this->getMessage()->withAddedHeader('content-type', 'text/html');
        $message = $message->withAddedHeader('content-type', 'text/plain');
        $this->assertCount(2, $message->getHeader('content-type'));
        $this->assertCount(2, $message->getHeader('Content-Type'));
        $this->assertCount(2, $message->getHeader('CONTENT-TYPE'));
        $emptyHeader = $message->getHeader('Bar');
        $this->assertCount(0, $emptyHeader);
        $this->assertIsArray($emptyHeader);
    }

    public function testGetHeaderLine()
    {
        $message = $this->getMessage()
            ->withAddedHeader('content-type', 'text/html')
            ->withAddedHeader('Content-Type', 'text/plain');

        $pattern = '#^text/html, ?text/plain$#';
        $this->assertMatchesRegexp($pattern, $message->getHeaderLine('content-type'));
        $this->assertMatchesRegexp($pattern, $message->getHeaderLine('Content-Type'));
        $this->assertMatchesRegexp($pattern, $message->getHeaderLine('CONTENT-TYPE'));

        $this->assertSame('', $message->getHeaderLine('Bar'));
    }

    public function testWithHeader()
    {
        $initialMessage = $this->getMessage();
        $original = clone $initialMessage;

        $message = $initialMessage->withHeader('content-type', 'text/html');
        $this->assertNotSame($initialMessage, $message);
        $this->assertEquals($initialMessage, $original, 'Message object MUST not be mutated');
        $this->assertEquals('text/html', $message->getHeaderLine('content-type'));

        $message = $initialMessage->withHeader('content-type', 'text/plain');
        $this->assertEquals('text/plain', $message->getHeaderLine('content-type'));

        $message = $initialMessage->withHeader('Content-TYPE', 'text/script');
        $this->assertEquals('text/script', $message->getHeaderLine('content-type'));

        $message = $initialMessage->withHeader('x-foo', ['bar', 'baz']);
        $this->assertMatchesRegexp('|bar, ?baz|', $message->getHeaderLine('x-foo'));
    }

    public static function getInvalidHeaderArguments()
    {
        return [
            [[], 'foo'],
            ['foo', []],
            ['', ''],
            ['foo', false],
            [false, 'foo'],
            ['foo', new \stdClass()],
            [new \stdClass(), 'foo'],
        ];
    }
    #[DataProvider('getInvalidHeaderArguments')]
    public function testWithHeaderInvalidArguments($name, $value)
    {
        $this->expectException(\InvalidArgumentException::class);
        $initialMessage = $this->getMessage();
        $initialMessage->withHeader($name, $value);
    }

    public function testWithAddedHeader()
    {
        $message = $this->getMessage()->withAddedHeader('content-type', 'text/html');
        $message = $message->withAddedHeader('CONTENT-type', 'text/plain');
        $this->assertMatchesRegexp('|text/html, ?text/plain|', $message->getHeaderLine('content-type'));
        $this->assertMatchesRegexp('|text/html, ?text/plain|', $message->getHeaderLine('Content-Type'));
    }

    #[DataProvider('getInvalidHeaderArguments')]
    public function testWithAddedHeaderInvalidArguments($name, $value)
    {
        $this->expectException(\InvalidArgumentException::class);
        $initialMessage = $this->getMessage();
        $initialMessage->withAddedHeader($name, $value);
    }

    /**
     * Make sure we maintain headers when we add array values.
     */
    public function testWithAddedHeaderArrayValue()
    {
        $message = $this->getMessage()->withAddedHeader('content-type', 'text/html');
        $message = $message->withAddedHeader('content-type', ['text/plain', 'application/json']);

        $headerLine = $message->getHeaderLine('content-type');
        $this->assertMatchesRegexp('|text/html|', $headerLine);
        $this->assertMatchesRegexp('|text/plain|', $headerLine);
        $this->assertMatchesRegexp('|application/json|', $headerLine);
    }

    /**
     * Make sure we maintain headers when we add array values with keys.
     */
    public function testWithAddedHeaderArrayValueAndKeys()
    {
        $message = $this->getMessage()->withAddedHeader('content-type', ['foo' => 'text/html']);
        $message = $message->withAddedHeader('content-type', ['foo' => 'text/plain', 'bar' => 'application/json']);

        $headerLine = $message->getHeaderLine('content-type');
        $this->assertMatchesRegexp('|text/html|', $headerLine);
        $this->assertMatchesRegexp('|text/plain|', $headerLine);
        $this->assertMatchesRegexp('|application/json|', $headerLine);
    }

    public function testWithoutHeader()
    {
        $message = $this->getMessage()->withAddedHeader('content-type', 'text/html');
        $message = $message->withAddedHeader('Age', '0');
        $message = $message->withAddedHeader('X-Foo', 'bar');

        $headers = $message->getHeaders();
        $headerCount = count($headers);
        $this->assertTrue(isset($headers['Age']));

        // Remove a header
        $message = $message->withoutHeader('age');
        $headers = $message->getHeaders();
        $this->assertCount($headerCount - 1, $headers);
        $this->assertFalse(isset($headers['Age']));
    }

    public function testBody()
    {
        $initialMessage = $this->getMessage();
        $original = clone $initialMessage;
        $stream = $this->buildStream('foo');
        $message = $initialMessage->withBody($stream);
        $this->assertNotSame($initialMessage, $message);
        $this->assertEquals($initialMessage, $original, 'Message object MUST not be mutated');

        $this->assertEquals($stream, $message->getBody());
    }

    private function assertMatchesRegexp(string $pattern, string $string, string $message = ''): void
    {
        $this->assertMatchesRegularExpression($pattern, $string, $message);
    }

    public function testHeaderValueWithTrailingLineFeedIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new Message())->withHeader('X-Test', "value\n");
    }

    public function testHeaderNameWithTrailingLineFeedIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new Message())->withAddedHeader("X-Test\n", 'value');
    }
}

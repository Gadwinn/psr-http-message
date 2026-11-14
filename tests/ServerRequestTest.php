<?php

declare(strict_types=1);

namespace G\Http\Message\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use G\Http\Message\{ServerRequest, UploadedFile};

final class ServerRequestTest extends TestCase
{
    /**
     * @var ServerRequestInterface
     */
    private $serverRequest;

    protected function setUp(): void
    {
        $this->serverRequest = new ServerRequest(serverParams: $_SERVER);
    }

    public function buildUploadableFile($data)
    {
        $resource = fopen('php://memory', 'rw');
        fwrite($resource, $data);
        fseek($resource, 0);
        return new UploadedFile($resource);
    }

    public function testGetServerParams()
    {
        $this->assertEquals($_SERVER, $this->serverRequest->getServerParams());
    }

    public function testGetCookieParams()
    {
        $this->assertEquals($_COOKIE, $this->serverRequest->getCookieParams());
    }

    public function testWithCookieParams()
    {
        $orgCookie = $_COOKIE;
        $new = $this->serverRequest->withCookieParams(['foo' => 'bar']);

        $this->assertEquals($orgCookie, $this->serverRequest->getCookieParams(), 'Super global $_COOKIE MUST NOT change.');
        $this->assertNotEquals($orgCookie, $new->getCookieParams());

        $this->assertArrayHasKey('foo', $new->getCookieParams());
    }

    public function testGetQueryParams()
    {
        $new = $this->serverRequest->withQueryParams(['foo' => 'bar']);
        $this->assertEmpty($this->serverRequest->getQueryParams(), 'withQueryParams MUST be immutable');

        $this->assertArrayHasKey('foo', $new->getQueryParams());
    }

    public function testGetUploadedFiles()
    {
        $file = $this->buildUploadableFile('foo');
        $new = $this->serverRequest->withUploadedFiles([$file]);
        $this->assertEmpty($this->serverRequest->getUploadedFiles(), 'withUploadedFiles MUST be immutable');

        $files = $new->getUploadedFiles();
        $this->assertCount(1, $files);
        $this->assertEquals($file, $files[0]);
    }

    public function testGetUploadedNormalizedFiles()
    {
        $normFiles = ServerRequest::normalizeFiles([
            'file' => [
                'name' => ['name1.jpg', 'name2.jpg'],
                'type' => ['image/jpeg', 'image/jpeg'],
                'tmp_name' => ['dir/name1.tmp', 'dir/name2.tmp'],
                'error' => ['0', '0'],
                'size' => ['123', '456'],
            ]
        ]);
        $new = $this->serverRequest->withUploadedFiles($normFiles);
        $this->assertEmpty($this->serverRequest->getUploadedFiles(), 'withUploadedFiles MUST be immutable');

        $files = $new->getUploadedFiles();
        $this->assertCount(1, $files);
        $this->assertCount(2, $files['file']);
        $this->assertEquals($normFiles, $files);
    }

    public static function validParsedBodyParams()
    {
        return [
            [null],
            [new \stdClass()],
            [['foo' => 'bar', 'baz']],
        ];
    }
    #[DataProvider('validParsedBodyParams')]
    public function testGetParsedBody($value)
    {
        $new = $this->serverRequest->withParsedBody($value);
        $this->assertNull($this->serverRequest->getParsedBody(), 'withParsedBody MUST be immutable');
        $this->assertEquals($value, $new->getParsedBody());
    }

    public static function invalidParsedBodyParams()
    {
        return [
            [4711],
            [47.11],
            ['foobar'],
            [true],
        ];
    }
    #[DataProvider('invalidParsedBodyParams')]
    public function testGetParsedBodyInvalid($value)
    {
        try {
            $this->serverRequest->withParsedBody($value);
            $this->fail('Should not be accepted');
        } catch (\InvalidArgumentException $e) {
            $this->assertNull($this->serverRequest->getParsedBody(), 'withParsedBody MUST be immutable');
        }
    }

    public function testGetAttributes()
    {
        $new = $this->serverRequest->withAttribute('foo', 'bar');
        $oldAttributes = $this->serverRequest->getAttributes();
        $this->assertIsArray($oldAttributes, 'getAttributes MUST return an array');
        $this->assertEmpty($oldAttributes, 'withAttribute MUST be immutable');
        $this->assertEquals(['foo' => 'bar'], $new->getAttributes());

        $new = $new->withAttribute('baz', 'biz');
        $this->assertEquals(['foo' => 'bar', 'baz' => 'biz'], $new->getAttributes());
    }

    public function testGetAttribute()
    {
        $new = $this->serverRequest->withAttribute('foo', 'bar');
        $this->assertEquals('bar', $new->getAttribute('foo'));
        $this->assertEquals('baz', $new->getAttribute('not found', 'baz'));
        $this->assertNull($new->getAttribute('not found'));
    }

    public function testWithoutAttribute()
    {
        $with = $this->serverRequest->withAttribute('foo', 'bar');
        $without = $with->withoutAttribute('foo');

        $this->assertEquals('bar', $with->getAttribute('foo'), 'withoutAttribute MUST be immutable');
        $this->assertNull($without->getAttribute('foo'));
    }

    public function testAttributeWithNullValue(): void
    {
        $serverRequest = (new ServerRequest())->withAttribute('name', null);

        $this->assertNull($serverRequest->getAttribute('name', 'default'));
        $this->assertSame('default', $serverRequest->getAttribute('missing', 'default'));
        $this->assertSame([], $serverRequest->withoutAttribute('name')->getAttributes());
    }
}

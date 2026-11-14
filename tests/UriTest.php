<?php

declare(strict_types=1);

namespace G\Http\Message\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;
use G\Http\Message\Uri;
use stdClass;
use TypeError;

final class UriTest extends TestCase
{
    /**
     * @var array with functionName => reason
     */
    protected $skippedTests = [];

    /**
     * @param string $uri
     *
     * @return UriInterface
     */
    public static function createUri($uri)
    {
        return new Uri($uri);
    }

    public function testScheme()
    {
        $uri = self::createUri('/');
        $this->assertSame('', $uri->getScheme());

        $uri = self::createUri('https://foo.com/');
        $this->assertEquals('https', $uri->getScheme());

        $newUri = $uri->withScheme('http');
        $this->assertNotSame($uri, $newUri);
        $this->assertEquals('http', $newUri->getScheme());
    }

    #[DataProvider('getInvalidSchemaArguments')]
    public function testWithSchemeInvalidArguments($schema)
    {
        $this->expectException(TypeError::class);
        self::createUri('/')->withScheme($schema);
    }

    public static function getInvalidSchemaArguments()
    {
        return [
            [true],
            [['foobar']],
            [34],
            [new stdClass()],
        ];
    }

    public function testAuthority()
    {
        $uri = self::createUri('/');
        $this->assertEquals('', $uri->getAuthority());

        $uri = self::createUri('http://foo@bar.com:80/');
        $this->assertEquals('foo@bar.com', $uri->getAuthority());

        $uri = self::createUri('http://foo@bar.com:81/');
        $this->assertEquals('foo@bar.com:81', $uri->getAuthority());

        $uri = self::createUri('http://user:foo@bar.com/');
        $this->assertEquals('user:foo@bar.com', $uri->getAuthority());
    }

    public function testUserInfo()
    {
        $uri = self::createUri('/');
        $this->assertEquals('', $uri->getUserInfo());

        $uri = self::createUri('http://user:foo@bar.com/');
        $this->assertEquals('user:foo', $uri->getUserInfo());

        $uri = self::createUri('http://foo@bar.com/');
        $this->assertEquals('foo', $uri->getUserInfo());
    }

    public function testHost()
    {
        $uri = self::createUri('/');
        $this->assertSame('', $uri->getHost());

        $uri = self::createUri('http://www.foo.com/');
        $this->assertEquals('www.foo.com', $uri->getHost());

        $uri = self::createUri('http://FOOBAR.COM/');
        $this->assertEquals('foobar.com', $uri->getHost());
    }

    public function testPort()
    {
        $uri = self::createUri('http://www.foo.com/');
        $this->assertNull($uri->getPort());

        $uri = self::createUri('http://www.foo.com:80/');
        $this->assertNull($uri->getPort());

        $uri = self::createUri('https://www.foo.com:443/');
        $this->assertNull($uri->getPort());

        $uri = self::createUri('http://www.foo.com:81/');
        $this->assertSame(81, $uri->getPort());
    }

    public static function getPaths()
    {
        return [
            [self::createUri('http://www.foo.com/'), '/'],
            [self::createUri('http://www.foo.com'), ''],
            [self::createUri('foo/bar'), 'foo/bar'],
            [self::createUri('http://www.foo.com/foo bar'), '/foo%20bar'],
            [self::createUri('http://www.foo.com/foo%20bar'), '/foo%20bar'],
            [self::createUri('http://www.foo.com/foo%2fbar'), '/foo%2Fbar'],
        ];
    }
    #[DataProvider('getPaths')]
    public function testPath(UriInterface $uri, $expected)
    {
        $this->assertSame($expected, $uri->getPath());
    }

    #[DataProvider('getQueries')]
    public function testQuery(UriInterface $uri, $expected)
    {
        $this->assertSame($expected, $uri->getQuery());
    }

    public static function getQueries()
    {
        return [
            [self::createUri('http://www.foo.com'), ''],
            [self::createUri('http://www.foo.com?'), ''],
            [self::createUri('http://www.foo.com?foo=bar'), 'foo=bar'],
            [self::createUri('http://www.foo.com?foo=bar%26baz'), 'foo=bar%26baz'],
            [self::createUri('http://www.foo.com?foo=bar&baz=biz'), 'foo=bar&baz=biz'],
        ];
    }

    #[DataProvider('getFragments')]
    public function testFragment(UriInterface $uri, $expected)
    {
        $this->assertEquals($expected, $uri->getFragment());
    }

    public static function getFragments()
    {
        return [
            [self::createUri('http://www.foo.com'), ''],
            [self::createUri('http://www.foo.com#'), ''],
            [self::createUri('http://www.foo.com#foo'), 'foo'],
            [self::createUri('http://www.foo.com#foo%20bar'), 'foo%20bar'],
        ];
    }

    public function testUriModification1()
    {
        $expected = 'https://0:0@0:1/0?0#0';
        $uri = self::createUri($expected);

        $this->assertInstanceOf(UriInterface::class, $uri);
        $this->assertSame($expected, (string) $uri);
    }

    public function testUriModification2()
    {
        $expected = 'https://0:0@0:1/0?0#0';
        $uri = $this
            ->createUri('')
            ->withHost('0')
            ->withPort(1)
            ->withUserInfo('0', '0')
            ->withScheme('https')
            ->withPath('/0')
            ->withQuery('0')
            ->withFragment('0');

        $this->assertInstanceOf(UriInterface::class, $uri);
        $this->assertSame($expected, (string) $uri);
    }

    public function testPathWithMultipleSlashes()
    {
        $expected = 'http://example.org//valid///path';
        $uri = self::createUri($expected);

        $this->assertInstanceOf(UriInterface::class, $uri);
        $this->assertSame($expected, (string) $uri);
        $this->assertSame('//valid///path', $uri->getPath());
    }
}

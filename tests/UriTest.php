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

    /**
     * @return array<string, array{string, string, string, string}>
     */
    public static function getEncodings(): array
    {
        return [
            // label => [raw value, expected path, expected query, expected fragment]
            'space' => ['a b', 'a%20b', 'a%20b', 'a%20b'],
            'utf-8' => ['café', 'caf%C3%A9', 'caf%C3%A9', 'caf%C3%A9'],
            'already encoded' => ['a%20b%C3%A9', 'a%20b%C3%A9', 'a%20b%C3%A9', 'a%20b%C3%A9'],
            'lowercase triplet' => ['a%c3%a9', 'a%C3%A9', 'a%C3%A9', 'a%C3%A9'],
            'lone percent' => ['100%', '100%25', '100%25', '100%25'],
            'percent not hex' => ['%zz%4', '%25zz%254', '%25zz%254', '%25zz%254'],
            'sub-delims' => ["!$&'()*+,;=", "!$&'()*+,;=", "!$&'()*+,;=", "!$&'()*+,;="],
            'colon and at' => ['a:b@c', 'a:b@c', 'a:b@c', 'a:b@c'],
            'unreserved' => ['aZ09-._~', 'aZ09-._~', 'aZ09-._~', 'aZ09-._~'],
            'slash and question mark' => ['a/b?c', 'a/b%3Fc', 'a/b?c', 'a/b?c'],
            'hash and brackets' => ['a#[b]', 'a%23%5Bb%5D', 'a%23%5Bb%5D', 'a%23%5Bb%5D'],
            'other gen-delims' => ['a"<>\\^`{|}', 'a%22%3C%3E%5C%5E%60%7B%7C%7D', 'a%22%3C%3E%5C%5E%60%7B%7C%7D', 'a%22%3C%3E%5C%5E%60%7B%7C%7D'],
        ];
    }

    #[DataProvider('getEncodings')]
    public function testWithersEncodeWithoutDoubleEncoding(string $raw, string $path, string $query, string $fragment): void
    {
        $uri = self::createUri('')->withPath($raw)->withQuery($raw)->withFragment($raw);

        $this->assertSame($path, $uri->getPath());
        $this->assertSame($query, $uri->getQuery());
        $this->assertSame($fragment, $uri->getFragment());

        // Encoding is idempotent.
        $again = $uri->withPath($uri->getPath())->withQuery($uri->getQuery())->withFragment($uri->getFragment());
        $this->assertSame((string) $uri, (string) $again);
    }

    public function testConstructorEncodesComponents(): void
    {
        $uri = self::createUri('http://example.com/caf%C3%A9/a b/+@:?q=a b&r=é/?#frag ment/?');

        $this->assertSame('/caf%C3%A9/a%20b/+@:', $uri->getPath());
        $this->assertSame('q=a%20b&r=%C3%A9/?', $uri->getQuery());
        $this->assertSame('frag%20ment/?', $uri->getFragment());
        $this->assertSame('http://example.com/caf%C3%A9/a%20b/+@:?q=a%20b&r=%C3%A9/?#frag%20ment/?', (string) $uri);
    }

    public function testUserInfoIsEncodedWithoutDoubleEncoding(): void
    {
        $uri = self::createUri('')->withUserInfo('us:er é', 'p@ss:w%20rd!');

        $this->assertSame('us%3Aer%20%C3%A9:p%40ss:w%20rd!', $uri->getUserInfo());
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

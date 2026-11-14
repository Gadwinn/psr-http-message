<?php

declare(strict_types=1);

namespace G\Http\Message\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use G\Http\Message\{ServerRequestFactory, UriFactory};

/**
 * Behaviour of the "from globals" factories across SAPI variations.
 * Superglobals are restored after each test (backupGlobals="true" in phpunit.xml).
 */
final class FromGlobalsTest extends TestCase
{
    protected function setUp(): void
    {
        $_GET = $_POST = $_COOKIE = $_FILES = [];
        foreach (array_keys($_SERVER) as $key) {
            if (
                str_starts_with((string) $key, 'HTTP_')
                || in_array($key, ['REQUEST_URI', 'QUERY_STRING', 'CONTENT_TYPE', 'HTTPS', 'SERVER_PORT'], true)
            ) {
                unset($_SERVER[$key]);
            }
        }
    }

    /**
     * @return array<string,array{string,string,?int}>
     */
    public static function hostProvider(): array
    {
        return [
            'name only'          => ['example.com', 'example.com', null],
            'name and port'      => ['example.com:8080', 'example.com', 8080],
            'default port'       => ['example.com:80', 'example.com', null],
            'ipv4 and port'      => ['127.0.0.1:8000', '127.0.0.1', 8000],
            'ipv6 literal'       => ['[::1]', '[::1]', null],
            'ipv6 literal, port' => ['[::1]:8080', '[::1]', 8080],
        ];
    }

    #[DataProvider('hostProvider')]
    public function testUriHostAndPortFromHostHeader(string $httpHost, string $host, ?int $port): void
    {
        $_SERVER['HTTP_HOST'] = $httpHost;

        $uri = UriFactory::createUriFromGlobals();

        $this->assertSame($host, $uri->getHost());
        $this->assertSame($port, $uri->getPort());
    }

    public function testUriIpv6ToString(): void
    {
        $_SERVER['HTTP_HOST'] = '[::1]:8080';
        $_SERVER['REQUEST_URI'] = '/a?b=1';

        $this->assertSame('http://[::1]:8080/a?b=1', (string) UriFactory::createUriFromGlobals());
    }

    public function testUriSchemeFromHttps(): void
    {
        $_SERVER['HTTP_HOST'] = 'example.com';

        $_SERVER['HTTPS'] = 'on';
        $this->assertSame('https', UriFactory::createUriFromGlobals()->getScheme());

        $_SERVER['HTTPS'] = 'off';
        $this->assertSame('http', UriFactory::createUriFromGlobals()->getScheme());
    }

    /**
     * @return array<string,array{string}>
     */
    public static function formContentTypeProvider(): array
    {
        return [
            'urlencoded'               => ['application/x-www-form-urlencoded'],
            'urlencoded with charset'  => ['application/x-www-form-urlencoded; charset=UTF-8'],
            'multipart with boundary'  => ['multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxk'],
            'mixed case, no space'     => ['Multipart/Form-Data;boundary=abc'],
        ];
    }

    #[DataProvider('formContentTypeProvider')]
    public function testParsedBodyOfFormPost(string $contentType): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_TYPE'] = $contentType;
        $_POST = ['name' => 'value'];

        $serverRequest = ServerRequestFactory::createServerRequestFromGlobals();

        $this->assertSame(['name' => 'value'], $serverRequest->getParsedBody());
    }

    public function testParsedBodyIgnoredForOtherContentTypes(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_POST = ['name' => 'value'];

        $this->assertNull(ServerRequestFactory::createServerRequestFromGlobals()->getParsedBody());
    }

    public function testQueryParamsFromRequestUriWhenGetIsEmpty(): void
    {
        $_SERVER['REQUEST_URI'] = '/path?a=1&b[]=2';

        $serverRequest = ServerRequestFactory::createServerRequestFromGlobals();

        $this->assertSame(['a' => '1', 'b' => ['2']], $serverRequest->getQueryParams());
        $this->assertSame('/path?a=1&b[]=2', $serverRequest->getRequestTarget());
    }

    public function testProtocolVersionFromServerProtocol(): void
    {
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/2.0';

        $this->assertSame('2.0', ServerRequestFactory::createServerRequestFromGlobals()->getProtocolVersion());
    }
}

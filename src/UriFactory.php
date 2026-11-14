<?php

declare(strict_types=1);

namespace G\Http\Message;

use Psr\Http\Message\{UriFactoryInterface, UriInterface};

/**
 * PSR-17
 * @version 1.0.0
 */
class UriFactory implements UriFactoryInterface
{
    protected const SERVER_HTTPS_OFF = 'off';

    public function createUri(string $uri = ''): UriInterface
    {
        return new Uri($uri);
    }

    /**
     * Create a new URI from globals.
     *
     * @return UriInterface
     */
    public static function createUriFromGlobals(): UriInterface
    {
        $uri = new Uri();

        // Scheme
        $https = isset($_SERVER['HTTPS']) && is_string($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : '';
        $uri = $uri->withScheme(
            $https === '' || strtolower($https) === self::SERVER_HTTPS_OFF ? 'http' : 'https'
        );

        // Host
        $httpHost = isset($_SERVER['HTTP_HOST']) && is_string($_SERVER['HTTP_HOST'])
            ? $_SERVER['HTTP_HOST'] : '';
        $serverName = isset($_SERVER['SERVER_NAME']) && is_string($_SERVER['SERVER_NAME'])
            ? $_SERVER['SERVER_NAME'] : '';
        $serverAddr = isset($_SERVER['SERVER_ADDR']) && is_string($_SERVER['SERVER_ADDR'])
            ? $_SERVER['SERVER_ADDR'] : '';
        // Split "host[:port]", keeping bracketed IPv6 literals ("[::1]:8080") intact.
        $hostPort = null;
        if ($httpHost !== '' && preg_match('/^(\[[^\]]+\]|[^:]*)(?::(\d+))?$/D', $httpHost, $matches) === 1) {
            $httpHost = $matches[1];
            $hostPort = isset($matches[2]) ? (int) $matches[2] : null;
        }
        if ($httpHost !== '') {
            $uri = $uri->withHost($httpHost);
        } elseif ($serverName !== '') {
            $uri = $uri->withHost($serverName);
        } elseif ($serverAddr !== '') {
            $uri = $uri->withHost($serverAddr);
        }

        // Port
        if ($hostPort !== null) {
            $uri = $uri->withPort($hostPort);
        } elseif (isset($_SERVER['SERVER_PORT'])) {
            $serverPort = $_SERVER['SERVER_PORT'];
            $uri = $uri->withPort(is_string($serverPort) || is_int($serverPort) ? (int) $serverPort : null);
        }

        // Path
        $requestUri = isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI'])
            ? $_SERVER['REQUEST_URI'] : '';
        if ($requestUri !== '') {
            $uri = $uri->withPath(explode('?', $requestUri)[0]);
        }

        // Query
        $queryString = isset($_SERVER['QUERY_STRING']) && is_string($_SERVER['QUERY_STRING'])
            ? $_SERVER['QUERY_STRING'] : '';
        if ($requestUri !== '' && strpos($requestUri, '?') !== false) {
            $uri = $uri->withQuery(explode('?', $requestUri, 2)[1]);
        } elseif ($queryString !== '') {
            $uri = $uri->withQuery($queryString);
        }

        return $uri;
    }
}

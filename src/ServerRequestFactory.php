<?php

declare(strict_types=1);

namespace G\Http\Message;

use Psr\Http\Message\{ServerRequestFactoryInterface, ServerRequestInterface, UriInterface};

/**
 * PSR-17
 * @version 1.0.0
 */
class ServerRequestFactory implements ServerRequestFactoryInterface
{
    protected const DEFAULT_REQUESTTARGET = '/';
    protected const DEFAULT_METHOD = 'GET';
    protected const CONTENT_TYPE_URLENCODED = 'application/x-www-form-urlencoded';
    protected const CONTENT_TYPE_MULTIPART = 'multipart/form-data';

    /**
     * @param string $method The HTTP method associated with the request.
     * @param UriInterface|string $uri The URI associated with the request. If
     *     the value is a string, the factory MUST create a UriInterface
     *     instance based on it.
     * @param array<string,string> $serverParams Array of SAPI parameters with which to seed
     *     the generated request instance.
     */
    public function createServerRequest(
        string $method,
        $uri,
        array $serverParams = []
    ): ServerRequestInterface {
        return new ServerRequest(method: $method, uri: $uri, serverParams: $serverParams);
    }

    /**
     * Create a new server request from globals.
     *
     * @return ServerRequestInterface
     */
    public static function createServerRequestFromGlobals(): ServerRequestInterface
    {
        // GET
        $queryParams = $_GET;
        if ($queryParams === []) {
            $requestUri = isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI'])
                ? $_SERVER['REQUEST_URI']
                : '';
            if ($requestUri !== '' && strpos($requestUri, '?') !== false) {
                parse_str(explode('?', $requestUri, 2)[1], $queryParams);
            } elseif (isset($_SERVER['QUERY_STRING']) && is_string($_SERVER['QUERY_STRING'])) {
                parse_str($_SERVER['QUERY_STRING'], $queryParams);
            }
        }

        $requestMethod = isset($_SERVER['REQUEST_METHOD']) && is_string($_SERVER['REQUEST_METHOD'])
            ? $_SERVER['REQUEST_METHOD']
            : self::DEFAULT_METHOD;
        // Media type only: drop parameters such as "; boundary=..." or "; charset=UTF-8"
        $contentType = strtolower(trim(explode(
            ';',
            isset($_SERVER['CONTENT_TYPE']) && is_string($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : ''
        )[0]));
        $parsedBody = null;

        if (strtoupper($requestMethod) === Message::METHOD_POST) {
            // PSR-7: POST + urlencoded or multipart MUST return $_POST
            if ($contentType === self::CONTENT_TYPE_URLENCODED || $contentType === self::CONTENT_TYPE_MULTIPART) {
                $parsedBody = $_POST ?: null;
            }
        } elseif (in_array(strtoupper($requestMethod), [Message::METHOD_PUT, Message::METHOD_PATCH], true)) {
            // PHP does not populate $_POST for PUT/PATCH; parse php://input for urlencoded only
            if ($contentType === self::CONTENT_TYPE_URLENCODED) {
                parse_str(file_get_contents('php://input') ?: '', $parsed);
                $parsedBody = $parsed ?: null;
            }
        }

        $serverProtocol = isset($_SERVER['SERVER_PROTOCOL']) && is_string($_SERVER['SERVER_PROTOCOL'])
            ? $_SERVER['SERVER_PROTOCOL']
            : '';
        $hasProtocolVersion = $serverProtocol !== '' && strpos($serverProtocol, '/') !== false;

        $serverRequestUri = isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI'])
            ? $_SERVER['REQUEST_URI']
            : self::DEFAULT_REQUESTTARGET;

        $headers = function_exists('getallheaders') ? getallheaders() : self::headersFromServerParams($_SERVER);

        $serverRequest = new ServerRequest(
            $hasProtocolVersion ? explode('/', $serverProtocol, 2)[1] : '1.1',
            $headers,
            fopen('php://input', 'r') ?: null,
            $serverRequestUri,
            $requestMethod,
            UriFactory::createUriFromGlobals(),
            $_SERVER,
            $_COOKIE,
            $queryParams,
            ServerRequest::normalizeFiles($_FILES),
            $parsedBody ?: null,
            []
        );

        return $serverRequest;
    }

    /**
     * Rebuild the request headers from SAPI parameters, for SAPIs without getallheaders().
     *
     * @param array<array-key,mixed> $serverParams
     *
     * @return array<string,string>
     */
    protected static function headersFromServerParams(array $serverParams): array
    {
        $headers = [];
        foreach ($serverParams as $key => $value) {
            if (!is_string($key) || !is_string($value) || $value === '') {
                continue;
            }
            if (str_starts_with($key, 'HTTP_')) {
                $key = substr($key, strlen('HTTP_'));
            } elseif (!in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'], true)) {
                continue;
            }
            $headers[ucwords(strtolower(str_replace('_', '-', $key)), '-')] = $value;
        }
        return $headers;
    }
}

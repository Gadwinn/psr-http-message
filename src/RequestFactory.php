<?php

declare(strict_types=1);

namespace G\Http\Message;

use Psr\Http\Message\{RequestFactoryInterface, RequestInterface};

/**
 * PSR-17
 * @version 1.0.0
 */
class RequestFactory implements RequestFactoryInterface
{
    public function createRequest(string $method, $uri): RequestInterface
    {
        return new Request(method: $method, uri: $uri);
    }
}

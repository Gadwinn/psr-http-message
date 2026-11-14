<?php

declare(strict_types=1);

namespace G\Http\Message;

use Psr\Http\Message\{ResponseFactoryInterface, ResponseInterface};

/**
 * PSR-17
 * @version 1.0.0
 */
class ResponseFactory implements ResponseFactoryInterface
{
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return new Response(code: $code, reasonPhrase: $reasonPhrase);
    }
}

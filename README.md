# PSR-7 HTTP Message / PSR-17 HTTP Factory

A PHP 8.3+ implementation of [PSR-7 HTTP Message](https://www.php-fig.org/psr/psr-7/) and [PSR-17 HTTP Factory](https://www.php-fig.org/psr/psr-17/) interfaces.

## Tech Stack

- PHP `>= 8.3`
- [`psr/http-message`](https://packagist.org/packages/psr/http-message) `^1.0 || ^2.0`
- [`psr/http-factory`](https://packagist.org/packages/psr/http-factory) `^1.0`

## Installation

```bash
composer require gadwinn/psr-http-message
```

## Namespace

```php
use G\Http\Message\{
    Message, Request, Response, ServerRequest,
    Stream, Uri, UploadedFile,
    RequestFactory, ResponseFactory, ServerRequestFactory,
    StreamFactory, UploadedFileFactory, UriFactory,
};
```

---

## Usage

### Uri

```php
use G\Http\Message\Uri;

$uri = new Uri('https://user:pass@example.com:8080/path?foo=bar#section');

$uri->getScheme();    // 'https'
$uri->getAuthority(); // 'user:pass@example.com:8080'
$uri->getUserInfo();  // 'user:pass'
$uri->getHost();      // 'example.com'
$uri->getPort();      // 8080  (null when it matches the scheme default)
$uri->getPath();      // '/path'
$uri->getQuery();     // 'foo=bar'
$uri->getFragment();  // 'section'
(string) $uri;        // 'https://user:pass@example.com:8080/path?foo=bar#section'

// Immutable withers
$new = $uri->withScheme('http')
           ->withHost('other.com')
           ->withPort(null)
           ->withPath('/api/v1')
           ->withQuery('x=1')
           ->withFragment('');
```

User info, path, query and fragment are percent-encoded as required by PSR-7 (RFC 3986), whether they
come from the constructor or a wither, and never double-encoded: `withPath('/a b/caf%C3%A9')` gives
`/a%20b/caf%C3%A9`. Sub-delimiters (`!$&'()*+,;=`), `:` and `@` are kept as is; a lone `%` becomes `%25`.

### Stream

```php
use G\Http\Message\Stream;

$stream = new Stream(fopen('php://temp', 'r+'));
$stream->write('Hello, World!');
$stream->rewind();

$stream->isReadable();  // true
$stream->isWritable();  // true
$stream->isSeekable();  // true
$stream->getSize();     // 13
$stream->tell();        // 0  (after rewind)
$stream->read(5);       // 'Hello'
$stream->getContents(); // ', World!'
(string) $stream;       // seeks to 0 then returns full content; never throws

$stream->close();
```

### Message

`Message` is the base class for `Request`, `Response`, and `ServerRequest`. All header operations are **case-insensitive**.

```php
use G\Http\Message\Message;

$message = new Message(
    protocolVersion: '1.1',
    headers: ['Content-Type' => 'application/json'],
    body: fopen('php://temp', 'r+')   // resource or StreamInterface
);

$message->getProtocolVersion();            // '1.1'
$message->hasHeader('content-type');       // true
$message->getHeader('Content-Type');       // ['application/json']
$message->getHeaderLine('content-type');   // 'application/json'
$message->getHeaders();                    // ['Content-Type' => ['application/json']]

// Immutable withers
$new = $message->withProtocolVersion('2.0')
               ->withHeader('Accept', 'application/json')
               ->withAddedHeader('Accept', 'text/html')
               ->withoutHeader('Content-Type');
```

### Request

```php
use G\Http\Message\Request;

$request = new Request(
    protocolVersion: '1.1',
    headers: ['Accept' => 'application/json'],
    body: null,
    requestTarget: '',
    method: 'POST',
    uri: 'https://api.example.com/users'
);

$request->getMethod();        // 'POST'
$request->getUri();           // UriInterface
$request->getRequestTarget(); // '/users'  (derived from URI when requestTarget is empty)

// Immutable withers
$new = $request->withMethod('PUT')
               ->withUri(new Uri('https://api.example.com/users/42'))
               ->withRequestTarget('/users/42');
```

Available method constants (defined on `Message`):

```php
Message::METHOD_GET     // 'GET'
Message::METHOD_POST    // 'POST'
Message::METHOD_PUT     // 'PUT'
Message::METHOD_PATCH   // 'PATCH'
Message::METHOD_DELETE  // 'DELETE'
Message::METHOD_HEAD    // 'HEAD'
Message::METHOD_OPTIONS // 'OPTIONS'
Message::METHOD_CONNECT // 'CONNECT'
Message::METHOD_TRACE   // 'TRACE'
```

### Response

```php
use G\Http\Message\Response;

$response = new Response(
    protocolVersion: '1.1',
    headers: ['Content-Type' => 'application/json'],
    body: fopen('php://temp', 'r+'),
    code: 200,
    reasonPhrase: ''   // auto-populated from RFC 7231 when empty
);

$response->getStatusCode();   // 200
$response->getReasonPhrase(); // 'OK'

// Immutable wither
$notFound = $response->withStatus(404);
$notFound->getReasonPhrase(); // 'Not Found'

$custom = $response->withStatus(422, 'Validation Failed');
```

Available status code constants (defined on `Response`):

```php
Response::HTTP_OK                    // 200
Response::HTTP_CREATED               // 201
Response::HTTP_NO_CONTENT            // 204
Response::HTTP_BAD_REQUEST           // 400
Response::HTTP_UNAUTHORIZED          // 401
Response::HTTP_FORBIDDEN             // 403
Response::HTTP_NOT_FOUND             // 404
Response::HTTP_UNPROCESSABLE_ENTITY  // 422
Response::HTTP_INTERNAL_SERVER_ERROR // 500
// ... full list in source
```

### ServerRequest

```php
use G\Http\Message\ServerRequest;

$request = new ServerRequest(
    protocolVersion: '1.1',
    headers: getallheaders() ?: [],
    body: fopen('php://input', 'r'),
    requestTarget: $_SERVER['REQUEST_URI'] ?? '/',
    method: $_SERVER['REQUEST_METHOD'] ?? 'GET',
    uri: 'https://example.com/api/users',
    serverParams: $_SERVER,
    cookieParams: $_COOKIE,
    queryParams: $_GET,
    uploadedFiles: ServerRequest::normalizeFiles($_FILES),
    parsedBody: $_POST ?: null,
    attributes: []
);

$request->getServerParams();   // array derived from $_SERVER
$request->getCookieParams();   // ['session_id' => 'abc123']
$request->getQueryParams();    // ['page' => '2']
$request->getUploadedFiles();  // tree of UploadedFileInterface
$request->getParsedBody();     // array|object|null
$request->getAttributes();     // []

// Immutable withers
$new = $request->withCookieParams(['token' => 'xyz'])
               ->withQueryParams(['page' => '3'])
               ->withParsedBody(['name' => 'Alice'])   // array|object|null
               ->withAttribute('user_id', 42)
               ->withoutAttribute('user_id');

$request->getAttribute('user_id');              // 42
$request->getAttribute('missing', 'default');   // 'default'
```

#### `$_FILES` normalization

`normalizeFiles()` converts PHP's `$_FILES` structure (which groups metadata by type) into a properly nested tree of `UploadedFileInterface` instances, including multi-file inputs.

```php
$uploadedFiles = ServerRequest::normalizeFiles($_FILES);
```

### UploadedFile

```php
use G\Http\Message\UploadedFile;

$file = new UploadedFile(
    streamOrFile: '/tmp/phpXXXXXX',   // path string, resource, or StreamInterface
    size: 4096,
    error: UPLOAD_ERR_OK,
    clientFilename: 'photo.jpg',
    clientMediaType: 'image/jpeg'
);

$file->getError();           // UPLOAD_ERR_OK
$file->getSize();            // 4096
$file->getClientFilename();  // 'photo.jpg'   (untrusted - client-supplied)
$file->getClientMediaType(); // 'image/jpeg'  (untrusted - client-supplied)

$stream = $file->getStream();   // StreamInterface; throws after moveTo()
$file->moveTo('/storage/uploads/photo.jpg');
```

---

## PSR-17 Factories

Factories are the recommended entry point when wiring dependencies via a PSR-11 container.

### RequestFactory

```php
use G\Http\Message\RequestFactory;

$factory = new RequestFactory();
$request = $factory->createRequest('GET', 'https://api.example.com/users');
```

### ResponseFactory

```php
use G\Http\Message\ResponseFactory;

$factory = new ResponseFactory();
$response = $factory->createResponse(201, 'Created');
```

### ServerRequestFactory

```php
use G\Http\Message\ServerRequestFactory;

// From explicit parameters (PSR-17)
$factory = new ServerRequestFactory();
$request = $factory->createServerRequest('POST', 'https://api.example.com/users', $_SERVER);

// From PHP superglobals (non-PSR-17 static helper)
$request = ServerRequestFactory::createServerRequestFromGlobals();
```

Notes on `createServerRequestFromGlobals()`:

- The parsed body is taken from `$_POST` for `POST` requests whose media type is
  `application/x-www-form-urlencoded` or `multipart/form-data` (parameters such as
  `; boundary=...` are ignored), and decoded from the body for url-encoded `PUT`/`PATCH`
  requests. Any other content type (JSON, ...) is left to the application or a middleware.
- Headers come from `getallheaders()` when the SAPI provides it, otherwise they are rebuilt
  from the `HTTP_*` server parameters.
- `X-Forwarded-*` headers are deliberately **not** interpreted: behind a reverse proxy, the
  scheme, host and port are those seen by PHP. Trusting forwarded headers is the job of a
  middleware that knows which proxies are trusted.

### StreamFactory

```php
use G\Http\Message\StreamFactory;

$factory = new StreamFactory();

$stream = $factory->createStream('{"key":"value"}');
$stream = $factory->createStreamFromFile('/var/log/app.log', 'r');
$stream = $factory->createStreamFromResource(fopen('php://temp', 'r+'));
```

Valid mode values for `createStreamFromFile()` follow the pattern `[rwaxce]+?[bt]?`
(e.g., `r`, `r+`, `w`, `w+`, `ab`, `x+t`).

### UploadedFileFactory

```php
use G\Http\Message\{UploadedFileFactory, StreamFactory};

$streamFactory = new StreamFactory();
$stream = $streamFactory->createStreamFromFile('/tmp/phpXXXXXX', 'r+');

$factory = new UploadedFileFactory();
$file = $factory->createUploadedFile(
    stream: $stream,
    size: 4096,
    error: UPLOAD_ERR_OK,
    clientFilename: 'document.pdf',
    clientMediaType: 'application/pdf'
);
```

### UriFactory

```php
use G\Http\Message\UriFactory;

$factory = new UriFactory();
$uri = $factory->createUri('https://example.com/path?q=1');

// From PHP superglobals (non-PSR-17 static helper)
$uri = UriFactory::createUriFromGlobals();
```

---

## Container Wiring (PSR-11 example)

```php
use G\Http\Message\{
    RequestFactory, ResponseFactory, ServerRequestFactory,
    StreamFactory, UploadedFileFactory, UriFactory,
};
use Psr\Http\Message\{
    RequestFactoryInterface, ResponseFactoryInterface, ServerRequestFactoryInterface,
    StreamFactoryInterface, UploadedFileFactoryInterface, UriFactoryInterface,
};

$container->bind(RequestFactoryInterface::class, RequestFactory::class);
$container->bind(ResponseFactoryInterface::class, ResponseFactory::class);
$container->bind(ServerRequestFactoryInterface::class, ServerRequestFactory::class);
$container->bind(StreamFactoryInterface::class, StreamFactory::class);
$container->bind(UploadedFileFactoryInterface::class, UploadedFileFactory::class);
$container->bind(UriFactoryInterface::class, UriFactory::class);
```

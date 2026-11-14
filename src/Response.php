<?php

declare(strict_types=1);

namespace G\Http\Message;

use Psr\Http\Message\{ResponseInterface, StreamInterface};
use InvalidArgumentException;

/**
 * PSR-7
 * @version 1.0.0
 */
class Response extends Message implements ResponseInterface
{
    protected const STATUS_CODE_MIN = 100;
    protected const STATUS_CODE_MAX = 599;

    // 1xx informational response
    public const HTTP_CONTINUE = 100;
    public const HTTP_SWITCHING_PROTOCOLS = 101;
    public const HTTP_PROCESSING = 102;
    public const HTTP_EARLY_HINTS = 103;
    // 2xx success
    public const HTTP_OK = 200;
    public const HTTP_CREATED = 201;
    public const HTTP_ACCEPTED = 202;
    public const HTTP_NON_AUTHORITATIVE_INFORMATION = 203;
    public const HTTP_NO_CONTENT = 204;
    public const HTTP_RESET_CONTENT = 205;
    public const HTTP_PARTIAL_CONTENT = 206;
    public const HTTP_MULTI_STATUS = 207;
    public const HTTP_ALREADY_REPORTED = 208;
    public const HTTP_CONTENT_DIFFERENT = 210;
    public const HTTP_IM_USED = 226;
    // 3xx redirection
    public const HTTP_MULTIPLE_CHOICES = 300;
    public const HTTP_MOVED_PERMANENTLY = 301;
    public const HTTP_FOUND = 302;
    public const HTTP_SEE_OTHER = 303;
    public const HTTP_NOT_MODIFIED = 304;
    public const HTTP_USE_PROXY = 305;
    public const HTTP_RESERVED = 306;
    public const HTTP_TEMPORARY_REDIRECT = 307;
    public const HTTP_PERMANENTLY_REDIRECT = 308;
    public const HTTP_TOO_MANY_REDIRECTS = 310;
    // 4xx client errors
    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_UNAUTHORIZED = 401;
    public const HTTP_PAYMENT_REQUIRED = 402;
    public const HTTP_FORBIDDEN = 403;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_METHOD_NOT_ALLOWED = 405;
    public const HTTP_NOT_ACCEPTABLE = 406;
    public const HTTP_PROXY_AUTHENTICATION_REQUIRED = 407;
    public const HTTP_REQUEST_TIMEOUT = 408;
    public const HTTP_CONFLICT = 409;
    public const HTTP_GONE = 410;
    public const HTTP_LENGTH_REQUIRED = 411;
    public const HTTP_PRECONDITION_FAILED = 412;
    public const HTTP_REQUEST_ENTITY_TOO_LARGE = 413;
    public const HTTP_REQUEST_URI_TOO_LONG = 414;
    public const HTTP_UNSUPPORTED_MEDIA_TYPE = 415;
    public const HTTP_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    public const HTTP_EXPECTATION_FAILED = 417;
    public const HTTP_I_AM_A_TEAPOT = 418;
    public const HTTP_PAGE_EXPIRED = 419;
    public const HTTP_MISDIRECTED_REQUEST = 421;
    public const HTTP_UNPROCESSABLE_ENTITY = 422;
    public const HTTP_LOCKED = 423;
    public const HTTP_FAILED_DEPENDENCY = 424;
    public const HTTP_TOO_EARLY = 425;
    public const HTTP_UPGRADE_REQUIRED = 426;
    public const HTTP_PRECONDITION_REQUIRED = 428;
    public const HTTP_TOO_MANY_REQUESTS = 429;
    public const HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
    public const HTTP_NO_RESPONSE = 444;
    public const HTTP_RETRY_WITH = 449;
    public const HTTP_BLOCKED_BY_WINDOWS_PARENTAL_CONTROLS = 450;
    public const HTTP_UNAVAILABLE_FOR_LEGAL_REASONS = 451;
    public const HTTP_UNRECOVERABLE_ERROR = 456;
    public const HTTP_SSL_CERTIFICATE_ERROR = 495;
    public const HTTP_SSL_CERTIFICATE_REQUIRED = 496;
    public const HTTP_HTTP_REQUEST_SENT_TO_HTTPS_PORT = 497;
    public const HTTP_TOKEN_EXPIRED_INVALID = 498;
    public const HTTP_CLIENT_CLOSED_REQUEST = 499;
    // 5xx server errors
    public const HTTP_INTERNAL_SERVER_ERROR = 500;
    public const HTTP_NOT_IMPLEMENTED = 501;
    public const HTTP_BAD_GATEWAY = 502;
    public const HTTP_SERVICE_UNAVAILABLE = 503;
    public const HTTP_GATEWAY_TIMEOUT = 504;
    public const HTTP_VERSION_NOT_SUPPORTED = 505;
    public const HTTP_VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL = 506;
    public const HTTP_INSUFFICIENT_STORAGE = 507;
    public const HTTP_LOOP_DETECTED = 508;
    public const HTTP_BANDWIDTH_LIMIT_EXCEEDED = 509;
    public const HTTP_NOT_EXTENDED = 510;
    public const HTTP_NETWORK_AUTHENTICATION_REQUIRED = 511;
    public const HTTP_UNKNOWN_ERROR = 520;
    public const HTTP_WEB_SERVER_IS_DOWN = 521;
    public const HTTP_CONNECTION_TIMED_OUT = 522;
    public const HTTP_ORIGIN_IS_UNREACHABLE = 523;
    public const HTTP_A_TIMEOUT_OCCURRED = 524;
    public const HTTP_SSL_HANDSHAKE_FAILED = 525;
    public const HTTP_INVALID_SSL_CERTIFICATE = 526;
    public const HTTP_RAILGUN_ERROR = 527;

    protected const PHRASES = [
        // 1xx informational response
        self::HTTP_CONTINUE => 'Continue', // [RFC9110, Section 15.2.1]
        self::HTTP_SWITCHING_PROTOCOLS => 'Switching Protocols', // [RFC9110, Section 15.2.2]
        self::HTTP_PROCESSING => 'Processing', // [RFC2518]
        self::HTTP_EARLY_HINTS => 'Early Hints', // [RFC8297]
        // 2xx success
        self::HTTP_OK => 'OK', // [RFC9110, Section 15.3.1]
        self::HTTP_CREATED => 'Created', // [RFC9110, Section 15.3.2]
        self::HTTP_ACCEPTED => 'Accepted', // [RFC9110, Section 15.3.3]
        self::HTTP_NON_AUTHORITATIVE_INFORMATION => 'Non-Authoritative Information', // [RFC9110, Section 15.3.4]
        self::HTTP_NO_CONTENT => 'No Content', // [RFC9110, Section 15.3.5]
        self::HTTP_RESET_CONTENT => 'Reset Content', // [RFC9110, Section 15.3.6]
        self::HTTP_PARTIAL_CONTENT => 'Partial Content', // [RFC9110, Section 15.3.7]
        self::HTTP_MULTI_STATUS => 'Multi-Status', // [RFC4918]
        self::HTTP_ALREADY_REPORTED => 'Already Reported', // [RFC5842]
        self::HTTP_CONTENT_DIFFERENT => 'Content Different', // WebDAV; RFC 2518
        self::HTTP_IM_USED => 'IM Used', // [RFC3229]
        // 3xx redirection
        self::HTTP_MULTIPLE_CHOICES => 'Multiple Choices', // [RFC9110, Section 15.4.1]
        self::HTTP_MOVED_PERMANENTLY => 'Moved Permanently', // [RFC9110, Section 15.4.2]
        self::HTTP_FOUND => 'Found', // [RFC9110, Section 15.4.3]
        self::HTTP_SEE_OTHER => 'See Other', // [RFC9110, Section 15.4.4]
        self::HTTP_NOT_MODIFIED => 'Not Modified', // [RFC9110, Section 15.4.5]
        self::HTTP_USE_PROXY => 'Use Proxy', // [RFC9110, Section 15.4.6]
        self::HTTP_RESERVED => '(Unused)', // [RFC9110, Section 15.4.7]
        self::HTTP_TEMPORARY_REDIRECT => 'Temporary Redirect', // [RFC9110, Section 15.4.8]
        self::HTTP_PERMANENTLY_REDIRECT => 'Permanent Redirect', // [RFC9110, Section 15.4.9]
        self::HTTP_TOO_MANY_REDIRECTS => 'Too many Redirects',
        // 4xx client errors
        self::HTTP_BAD_REQUEST => 'Bad Request', // [RFC9110, Section 15.5.1]
        self::HTTP_UNAUTHORIZED => 'Unauthorized', // [RFC9110, Section 15.5.2]
        self::HTTP_PAYMENT_REQUIRED => 'Payment Required', // [RFC9110, Section 15.5.3]
        self::HTTP_FORBIDDEN => 'Forbidden', // [RFC9110, Section 15.5.4]
        self::HTTP_NOT_FOUND => 'Not Found', // [RFC9110, Section 15.5.5]
        self::HTTP_METHOD_NOT_ALLOWED => 'Method Not Allowed', // [RFC9110, Section 15.5.6]
        self::HTTP_NOT_ACCEPTABLE => 'Not Acceptable', // [RFC9110, Section 15.5.7]
        self::HTTP_PROXY_AUTHENTICATION_REQUIRED => 'Proxy Authentication Required', // [RFC9110, Section 15.5.8]
        self::HTTP_REQUEST_TIMEOUT => 'Request Timeout', // [RFC9110, Section 15.5.9]
        self::HTTP_CONFLICT => 'Conflict', // [RFC9110, Section 15.5.10]
        self::HTTP_GONE => 'Gone', // [RFC9110, Section 15.5.11]
        self::HTTP_LENGTH_REQUIRED => 'Length Required', // [RFC9110, Section 15.5.12]
        self::HTTP_PRECONDITION_FAILED => 'Precondition Failed', // [RFC9110, Section 15.5.13]
        self::HTTP_REQUEST_ENTITY_TOO_LARGE => 'Content Too Large', // [RFC9110, Section 15.5.14]
        self::HTTP_REQUEST_URI_TOO_LONG => 'URI Too Long', // [RFC9110, Section 15.5.15]
        self::HTTP_UNSUPPORTED_MEDIA_TYPE => 'Unsupported Media Type', // [RFC9110, Section 15.5.16]
        self::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE => 'Range Not Satisfiable', // [RFC9110, Section 15.5.17]
        self::HTTP_EXPECTATION_FAILED => 'Expectation Failed', // [RFC9110, Section 15.5.18]
        self::HTTP_I_AM_A_TEAPOT => '(Unused)', // [RFC9110, Section 15.5.19]
        self::HTTP_PAGE_EXPIRED => 'Page expired',
        self::HTTP_MISDIRECTED_REQUEST => 'Misdirected Request', // [RFC9110, Section 15.5.20]
        self::HTTP_UNPROCESSABLE_ENTITY => 'Unprocessable Content', // [RFC9110, Section 15.5.21]
        self::HTTP_LOCKED => 'Locked', // [RFC4918]
        self::HTTP_FAILED_DEPENDENCY => 'Failed Dependency', // [RFC4918]
        self::HTTP_TOO_EARLY => 'Too Early', // [RFC8470]
        self::HTTP_UPGRADE_REQUIRED => 'Upgrade Required', // [RFC9110, Section 15.5.22]
        self::HTTP_PRECONDITION_REQUIRED => 'Precondition Required', // [RFC6585]
        self::HTTP_TOO_MANY_REQUESTS => 'Too Many Requests', // [RFC6585]
        self::HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE => 'Request Header Fields Too Large', // [RFC6585]
        self::HTTP_NO_RESPONSE => 'No Response', // Nginx
        self::HTTP_RETRY_WITH => 'Retry With', // Microsoft
        self::HTTP_BLOCKED_BY_WINDOWS_PARENTAL_CONTROLS => 'Blocked by Windows Parental Controls', // Microsoft
        self::HTTP_UNAVAILABLE_FOR_LEGAL_REASONS => 'Unavailable For Legal Reasons', // [RFC7725]
        self::HTTP_UNRECOVERABLE_ERROR => 'Unrecoverable Error', // WebDav
        self::HTTP_SSL_CERTIFICATE_ERROR => 'SSL Certificate Error', // Nginx
        self::HTTP_SSL_CERTIFICATE_REQUIRED => 'SSL Certificate Required', // Nginx
        self::HTTP_HTTP_REQUEST_SENT_TO_HTTPS_PORT => 'HTTP Request Sent to HTTPS Port', // Nginx
        self::HTTP_TOKEN_EXPIRED_INVALID => 'Token expired/invalid', // Nginx
        self::HTTP_CLIENT_CLOSED_REQUEST => 'Client Closed Request', // Nginx
        // 5xx server errors
        self::HTTP_INTERNAL_SERVER_ERROR => 'Internal Server Error', // [RFC9110, Section 15.6.1]
        self::HTTP_NOT_IMPLEMENTED => 'Not Implemented', // [RFC9110, Section 15.6.2]
        self::HTTP_BAD_GATEWAY => 'Bad Gateway', // [RFC9110, Section 15.6.3]
        self::HTTP_SERVICE_UNAVAILABLE => 'Service Unavailable', // [RFC9110, Section 15.6.4]
        self::HTTP_GATEWAY_TIMEOUT => 'Gateway Timeout', // [RFC9110, Section 15.6.5]
        self::HTTP_VERSION_NOT_SUPPORTED => 'HTTP Version Not Supported', // [RFC9110, Section 15.6.6]
        self::HTTP_VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL => 'Variant Also Negotiates', // [RFC2295]
        self::HTTP_INSUFFICIENT_STORAGE => 'Insufficient Storage', // [RFC4918]
        self::HTTP_LOOP_DETECTED => 'Loop Detected', // [RFC5842]
        self::HTTP_BANDWIDTH_LIMIT_EXCEEDED => 'Bandwidth Limit Exceeded',
        self::HTTP_NOT_EXTENDED => 'Not Extended (OBSOLETED)', // [RFC2774][status-change-http-experiments-to-historic]
        self::HTTP_NETWORK_AUTHENTICATION_REQUIRED => 'Network Authentication Required', // [RFC6585]
        self::HTTP_UNKNOWN_ERROR => 'Unknown Error', // Cloudflare
        self::HTTP_WEB_SERVER_IS_DOWN => 'Web Server Is Down', // Cloudflare
        self::HTTP_CONNECTION_TIMED_OUT => 'Connection Timed Out', // Cloudflare
        self::HTTP_ORIGIN_IS_UNREACHABLE => 'Origin Is Unreachable', // Cloudflare
        self::HTTP_A_TIMEOUT_OCCURRED => 'A Timeout Occurred', // Cloudflare
        self::HTTP_SSL_HANDSHAKE_FAILED => 'SSL Handshake Failed', // Cloudflare
        self::HTTP_INVALID_SSL_CERTIFICATE => 'Invalid SSL Certificate', // Cloudflare
        self::HTTP_RAILGUN_ERROR => 'Railgun Error', // Cloudflare
    ];

    protected int $code;
    protected string $reasonPhrase;

    /**
     * @param string $protocolVersion
     * @param array<array-key,mixed> $headers
     * @param StreamInterface|resource|null $body
     * @param int $code
     * @param string $reasonPhrase
     */
    public function __construct(
        string $protocolVersion = '1.1',
        array $headers = [],
        $body = null,
        int $code = 200,
        string $reasonPhrase = ''
    ) {
        parent::__construct($protocolVersion, $headers, $body);

        $this->setStatus($code, $reasonPhrase);
    }

    public function getStatusCode(): int
    {
        return $this->code;
    }

    public function withStatus($code, $reasonPhrase = ''): ResponseInterface
    {
        $response = clone $this;
        $response->setStatus($code, $reasonPhrase);
        return $response;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    /* --- */

    /**
     * @param int $code
     * @param string $reasonPhrase
     *
     * @return Response
     * @throws InvalidArgumentException
     */
    protected function setStatus($code, $reasonPhrase = ''): self
    {
        $code = filter_var($code, FILTER_VALIDATE_INT);
        if (!$code || $code < self::STATUS_CODE_MIN || self::STATUS_CODE_MAX < $code) {
            throw new InvalidArgumentException('Invalid code');
        }

        if ($reasonPhrase === '' && isset(self::PHRASES[$code])) {
            $reasonPhrase = self::PHRASES[$code];
        }

        $this->code = $code;
        $this->reasonPhrase = (string) $reasonPhrase;
        return $this;
    }
}

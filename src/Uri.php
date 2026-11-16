<?php

declare(strict_types=1);

namespace G\Http\Message;

use Psr\Http\Message\UriInterface;
use InvalidArgumentException;

/**
 * PSR-7
 * @version 1.0.0
 */
class Uri implements UriInterface
{
    protected const DEFAULT_PORTS = [
        'ftp' => 21,
        'ftps' => 21,
        'ssh' => 22,
        'sftp' => 22,
        'telnet' => 23,
        'tn3270' => 23,
        'gopher' => 70,
        'http'  => 80,
        'pop' => 110,
        'nntp' => 119,
        'news' => 119,
        'imap' => 143,
        'ldap' => 389,
        'https' => 443,
    ];
    protected const PORT_MIN = 0;
    protected const PORT_MAX = 65535;
    /** RFC 3986 unreserved characters, as a regex character class body. */
    protected const CHARS_UNRESERVED = 'a-zA-Z0-9_\-\.~';
    /** RFC 3986 sub-delimiters, as a regex character class body. */
    protected const CHARS_SUB_DELIMS = '!\$&\'\(\)\*\+,;=';

    protected string $scheme = '';
    protected string $userInfo = '';
    protected string $host = '';
    protected ?int $port = null;
    protected string $path = '';
    protected string $query = '';
    protected string $fragment = '';

    /**
     * @param string $uri
     *
     * @return UriInterface
     * @throws InvalidArgumentException
     */
    public function __construct(string $uri = '')
    {
        if ($uri !== '') {
            $this->parse($uri);
        }
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getAuthority(): string
    {
        $authority = '';

        if ($this->userInfo !== '') {
            $authority .= $this->userInfo . '@';
        }

        $authority .= $this->host;

        if ($this->port !== null) {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }

    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    public function withScheme(string $scheme): UriInterface
    {
        $uri = clone $this;
        $uri->setScheme($scheme);
        $uri->setPort($uri->port);
        return $uri;
    }

    public function withUserInfo(string $user, ?string $password = null): UriInterface
    {
        $uri = clone $this;
        $uri->setUserInfo($user, $password);
        return $uri;
    }

    public function withHost(string $host): UriInterface
    {
        $uri = clone $this;
        $uri->setHost($host);
        return $uri;
    }

    public function withPort(?int $port): UriInterface
    {
        $uri = clone $this;
        $uri->setPort($port);
        return $uri;
    }

    public function withPath(string $path): UriInterface
    {
        $uri = clone $this;
        $uri->setPath($path);
        return $uri;
    }

    public function withQuery(string $query): UriInterface
    {
        $uri = clone $this;
        $uri->setQuery($query);
        return $uri;
    }

    public function withFragment(string $fragment): UriInterface
    {
        $uri = clone $this;
        $uri->setFragment($fragment);
        return $uri;
    }

    public function __toString(): string
    {
        $string = '';

        if ($this->scheme !== '') {
            $string .= $this->scheme . ':';
        }

        if ($this->getAuthority() !== '') {
            $string .= '//' . $this->getAuthority();
        }

        $path = $this->path;
        if ($this->getAuthority() !== '') {
            if ($path !== '' && $path[0] !== strval('/')) {
                $path = '/' . $path;
            }
        } elseif (str_starts_with($path, '//')) {
            $path = '/' . ltrim($path, '/');
        }
        $string .= $path;

        if ($this->query !== '') {
            $string .= '?' . $this->query;
        }

        if ($this->fragment !== '') {
            $string .= '#' . $this->fragment;
        }

        return $string;
    }

    /* --- */

    /**
     * @param string $uri
     *
     * @return Uri
     * @throws InvalidArgumentException
     */
    protected function parse(string $uri): self
    {
        $parsed = parse_url($uri);

        if ($parsed === false) {
            throw new InvalidArgumentException('Invalid uri');
        }

        if (isset($parsed['scheme'])) {
            $this->setScheme($parsed['scheme']);
        }

        if (isset($parsed['user'])) {
            $this->setUserInfo($parsed['user'], $parsed['pass'] ?? null);
        }

        if (isset($parsed['host'])) {
            $this->setHost($parsed['host']);
        }

        if (isset($parsed['port'])) {
            $this->setPort($parsed['port']);
        }

        if (isset($parsed['path'])) {
            $this->setPath($parsed['path']);
        }

        if (isset($parsed['query'])) {
            $this->setQuery($parsed['query']);
        }

        if (isset($parsed['fragment'])) {
            $this->setFragment($parsed['fragment']);
        }

        return $this;
    }

    /**
     * @param string $scheme
     *
     * @return Uri
     * @throws InvalidArgumentException
     */
    protected function setScheme(string $scheme): self
    {
        $this->scheme = strtolower($scheme);
        return $this;
    }

    /**
     * @param string $user
     * @param ?string $password
     *
     * @return Uri
     * @throws InvalidArgumentException
     */
    protected function setUserInfo(string $user, ?string $password): self
    {
        $userInfo = $this->encode($user, '');
        if ($password !== null) {
            $userInfo .= ':' . $this->encode($password, ':');
        }

        $this->userInfo = $userInfo;
        return $this;
    }

    /**
     * @param string $host
     *
     * @return Uri
     * @throws InvalidArgumentException
     */
    protected function setHost(string $host): self
    {
        $this->host = mb_strtolower($host);
        return $this;
    }

    /**
     * @param ?int $port
     *
     * @return Uri
     * @throws InvalidArgumentException
     */
    protected function setPort(?int $port): self
    {
        if ($port !== null) {
            if ($port < self::PORT_MIN || self::PORT_MAX < $port) {
                throw new InvalidArgumentException('Invalid port');
            }
        }

        if ((self::DEFAULT_PORTS[$this->scheme] ?? 0) === $port) {
            $port = null;
        }

        $this->port = $port;
        return $this;
    }

    /**
     * @param string $path
     *
     * @return Uri
     * @throws InvalidArgumentException
     */
    protected function setPath(string $path): self
    {
        $this->path = $this->encode($path, ':@\/');
        return $this;
    }

    /**
     * @param string $query
     *
     * @return Uri
     * @throws InvalidArgumentException
     */
    protected function setQuery(string $query): self
    {
        $this->query = $this->encode($query, ':@\/\?');
        return $this;
    }

    /**
     * @param string $fragment
     *
     * @return Uri
     * @throws InvalidArgumentException
     */
    protected function setFragment(string $fragment): self
    {
        $this->fragment = $this->encode($fragment, ':@\/\?');
        return $this;
    }

    /**
     * Percent-encodes every character that is neither unreserved, a sub-delimiter, one of
     * the extra allowed characters, nor part of an existing percent-encoded triplet
     * (RFC 3986). Existing triplets are kept (normalized to uppercase, RFC 3986 §2.1):
     * a value is never double-encoded.
     *
     * @param string $value
     * @param string $extraChars Extra allowed characters, as a regex character class body
     *
     * @return string
     * @throws InvalidArgumentException
     */
    protected function encode(string $value, string $extraChars): string
    {
        $encoded = preg_replace_callback(
            '/(?:[^' . self::CHARS_UNRESERVED . self::CHARS_SUB_DELIMS . $extraChars . '%]++|%(?:[A-Fa-f0-9]{2})?)/',
            static fn (array $match): string => preg_match('/^%[A-Fa-f0-9]{2}$/D', $match[0]) === 1
                ? strtoupper($match[0])
                : rawurlencode($match[0]),
            $value
        );

        if ($encoded === null) {
            throw new InvalidArgumentException('Unable to encode the uri component');
        }

        return $encoded;
    }
}

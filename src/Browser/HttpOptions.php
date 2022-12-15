<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @phpstan-type RequiredOptions = array{
 *     headers: array<string,string>,
 *     query: mixed[],
 *     files: array<string,string>,
 *     server: array<string,string>,
 *     body: string|mixed[]|null,
 *     json: mixed,
 *     ajax: bool
 * }
 * @phpstan-type Options = array{
 *     headers?: array<string,string>,
 *     query?: mixed[],
 *     files?: array<string,string>,
 *     server?: array<string,string>,
 *     body?: string|mixed[]|null,
 *     json?: mixed,
 *     ajax?: bool
 * }
 */
class HttpOptions
{
    private const EMPTY_JSON_TRIGGER = '__JSON__';
    private const DEFAULT_OPTIONS = [
        // request headers
        'headers' => [],

        // query parameters
        'query' => [],

        // files to include
        'files' => [],

        // server variables
        'server' => [],

        // request body
        'body' => null,

        // if set, will json_encode and use as the body and
        // set the Content-Type/Accept request headers to application/json
        'json' => null,

        // if true will set the X-Requested-With request header to XMLHttpRequest
        'ajax' => false,
    ];

    /** @var RequiredOptions */
    private array $options;

    /**
     * @param Options $options
     */
    final public function __construct(array $options = [])
    {
        $this->options = \array_merge(self::DEFAULT_OPTIONS, $options);
    }

    /**
     * @param static|Options $options
     *
     * @return static
     */
    final public static function create($options = []): self
    {
        if ($options instanceof static) {
            return $options;
        }

        return new static($options);
    }

    /**
     * @param mixed $body
     *
     * @return static
     */
    final public static function json($body = null): self
    {
        return static::create()->asJson($body);
    }

    /**
     * @return static
     */
    final public static function ajax(): self
    {
        return static::create()->asAjax();
    }

    /**
     * @param mixed $body
     *
     * @return static
     */
    final public static function jsonAjax($body = null): self
    {
        return static::json($body)->asAjax();
    }

    /**
     * @param static|Options $options
     *
     * @return static
     */
    final public function merge($options = []): self
    {
        $other = self::create($options);

        // merge array options
        $this->options['headers'] = \array_merge($this->options['headers'], $other->options['headers']);
        $this->options['query'] = \array_merge($this->options['query'], $other->options['query']);
        $this->options['files'] = \array_merge($this->options['files'], $other->options['files']);
        $this->options['server'] = \array_merge($this->options['server'], $other->options['server']);

        // override value options only if different from default
        if ($other->options['body'] !== self::DEFAULT_OPTIONS['body']) {
            $this->options['body'] = $other->options['body'];
        }

        if ($other->options['json'] !== self::DEFAULT_OPTIONS['json']) {
            $this->options['json'] = $other->options['json'];
        }

        if ($other->options['ajax'] !== self::DEFAULT_OPTIONS['ajax']) {
            $this->options['ajax'] = $other->options['ajax'];
        }

        return $this;
    }

    /**
     * @return static
     */
    final public function withHeader(string $header, string $value): self
    {
        $this->options['headers'][$header] = $value;

        return $this;
    }

    /**
     * @param array<string,string> $headers
     *
     * @return static
     */
    final public function withHeaders(array $headers): self
    {
        $this->options['headers'] = $headers;

        return $this;
    }

    /**
     * @param mixed[] $query
     *
     * @return static
     */
    final public function withQuery(array $query): self
    {
        $this->options['query'] = $query;

        return $this;
    }

    /**
     * @param array<string,string> $server
     *
     * @return static
     */
    final public function withServer(array $server): self
    {
        $this->options['server'] = $server;

        return $this;
    }

    /**
     * @param array<string,string> $files
     *
     * @return static
     */
    final public function withFiles(array $files): self
    {
        $this->options['files'] = $files;

        return $this;
    }

    /**
     * @param string|mixed[]|null $body
     *
     * @return static
     */
    final public function withBody($body): self
    {
        $this->options['body'] = $body;

        return $this;
    }

    /**
     * @param mixed $body Any value that can be json encoded
     *
     * @return static
     */
    final public function asJson($body = null): self
    {
        $this->options['json'] = $body ?? self::EMPTY_JSON_TRIGGER;

        return $this;
    }

    /**
     * @return static
     */
    final public function asAjax(): self
    {
        $this->options['ajax'] = true;

        return $this;
    }

    final public function addQueryToUrl(string $url): string
    {
        if (false === $parts = \parse_url($url)) {
            throw new \InvalidArgumentException(\sprintf('Url "%s" is invalid.', $url));
        }

        if (isset($parts['query'])) {
            \parse_str($parts['query'], $query);
        } else {
            $query = [];
        }

        // merge query on url with the query option
        $parts['query'] = \http_build_query(\array_merge($query, $this->options['query']));

        $scheme = isset($parts['scheme']) ? $parts['scheme'].'://' : '';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $user = $parts['user'] ?? '';
        $pass = isset($parts['pass']) ? ':'.$parts['pass'] : '';
        $pass = ($user || $pass) ? "{$pass}@" : '';
        $path = $parts['path'] ?? '';
        $query = $parts['query'] ? '?'.$parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        return $scheme.$user.$pass.$host.$port.$path.$query.$fragment;
    }

    /**
     * @internal
     *
     * @return mixed[]
     */
    final public function parameters(): array
    {
        // when body is array, use as request parameters
        return \is_array($this->options['body']) ? $this->options['body'] : [];
    }

    /**
     * @internal
     *
     * @return array<string,string>
     */
    final public function files(): array
    {
        return $this->options['files'];
    }

    /**
     * @co-author Kévin Dunglas <dunglas@gmail.com>
     *
     * @internal
     *
     * @return array<string,string>
     */
    final public function server(): array
    {
        $server = $this->options['server'];
        $headers = \array_combine(
            \array_map(
                static fn($header) => \mb_strtoupper(\str_replace('-', '_', $header)),
                \array_keys($this->options['headers'])
            ),
            $this->options['headers']
        );

        if (null !== $this->options['json'] && !\array_key_exists('ACCEPT', $headers)) {
            $headers['ACCEPT'] = 'application/json';
        }

        if (null !== $this->options['json'] && !\array_key_exists('CONTENT_TYPE', $headers)) {
            $headers['CONTENT_TYPE'] = 'application/json';
        }

        if (false !== $this->options['ajax'] && !\array_key_exists('X_REQUESTED_WITH', $headers)) {
            $headers['X_REQUESTED_WITH'] = 'XMLHttpRequest';
        }

        foreach ($headers as $header => $value) {
            // content type header cannot have HTTP_ prefix
            if ('CONTENT_TYPE' !== $header) {
                $header = "HTTP_{$header}";
            }

            $server[$header] = $value;
        }

        return $server;
    }

    /**
     * @internal
     */
    final public function body(): ?string
    {
        if (\is_array($this->options['body'])) {
            // when body is array, it's used as the request parameters
            return null;
        }

        if (null === $this->options['json']) {
            return $this->options['body'];
        }

        if (self::EMPTY_JSON_TRIGGER === $this->options['json']) {
            return null;
        }

        return \json_encode($this->options['json'], \JSON_THROW_ON_ERROR);
    }
}

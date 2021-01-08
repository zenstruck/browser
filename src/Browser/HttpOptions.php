<?php

namespace Zenstruck\Browser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class HttpOptions
{
    private const EMPTY_JSON_TRIGGER = '__JSON__';
    private const DEFAULT_OPTIONS = [
        // request headers
        'headers' => [],

        // query parameters
        'parameters' => [],

        // files to include
        'files' => [],

        // server variables
        'server' => [],

        // raw request body as string
        'body' => null,

        // if set, will json_encode and use as the body and
        // set the Content-Type/Accept request headers to application/json
        'json' => null,

        // if true will set the X-Requested-With request header to XMLHttpRequest
        'ajax' => false,
    ];

    private array $options;

    final public function __construct(array $options = [])
    {
        $this->options = \array_merge(self::DEFAULT_OPTIONS, $options);
    }

    /**
     * @param self|array $options
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
     * @return static
     */
    final public static function jsonAjax($body = null): self
    {
        return static::json($body)->asAjax();
    }

    /**
     * @param self|array $options
     *
     * @return static
     */
    final public function merge($options = []): self
    {
        $other = self::create($options);

        // merge array options
        $this->options['headers'] = \array_merge($this->options['headers'], $other->options['headers']);
        $this->options['parameters'] = \array_merge($this->options['parameters'], $other->options['parameters']);
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
     * @return static
     */
    final public function withHeaders(array $headers): self
    {
        $this->options['headers'] = $headers;

        return $this;
    }

    /**
     * @return static
     */
    final public function withParameters(array $parameters): self
    {
        $this->options['parameters'] = $parameters;

        return $this;
    }

    /**
     * @return static
     */
    final public function withServer(array $server): self
    {
        $this->options['server'] = $server;

        return $this;
    }

    /**
     * @return static
     */
    final public function withFiles(array $files): self
    {
        $this->options['files'] = $files;

        return $this;
    }

    /**
     * @return static
     */
    final public function withBody(?string $body): self
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

    final public function parameters(): array
    {
        return $this->options['parameters'];
    }

    final public function files(): array
    {
        return $this->options['files'];
    }

    /**
     * @co-author Kévin Dunglas <dunglas@gmail.com>
     */
    final public function server(): array
    {
        $server = $this->options['server'];
        $headers = $this->options['headers'];

        if (null !== $this->options['json']) {
            $headers['Content-Type'] = $headers['Accept'] = 'application/json';
        }

        if (false !== $this->options['ajax']) {
            $headers['X-Requested-With'] = 'XMLHttpRequest';
        }

        foreach ($headers as $header => $value) {
            $header = \mb_strtoupper(\str_replace('-', '_', $header));

            // content type header cannot have HTTP_ prefix
            if ('CONTENT_TYPE' !== $header) {
                $header = "HTTP_{$header}";
            }

            $server[$header] = $value;
        }

        return $server;
    }

    final public function body(): ?string
    {
        if (null === $this->options['json']) {
            return $this->options['body'];
        }

        if (self::EMPTY_JSON_TRIGGER === $this->options['json']) {
            return null;
        }

        return \json_encode($this->options['json'], JSON_THROW_ON_ERROR);
    }
}

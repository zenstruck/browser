<?php

namespace Zenstruck\Browser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class HttpOptions
{
    private const DEFAULT_OPTIONS = [
        'headers' => [],
        'parameters' => [],
        'files' => [],
        'server' => [],
        'body' => null,
        'json' => null,
        'ajax' => false,
    ];

    private array $options;

    final public function __construct(array $options = [])
    {
        $this->options = \array_merge(self::DEFAULT_OPTIONS, $options);

        if ($this->options['json']) {
            $this->asJson($this->options['json']);
        }

        if ($this->options['ajax']) {
            $this->asAjax();
        }
    }

    /**
     * @param self|array $value
     */
    final public static function create($value = []): self
    {
        if ($value instanceof static) {
            return $value;
        }

        return new static($value);
    }

    final public static function json($body = null): self
    {
        return static::create()->asJson($body);
    }

    final public static function ajax(): self
    {
        return static::create()->asAjax();
    }

    final public static function jsonAjax($body = null): self
    {
        return static::json($body)->asAjax();
    }

    final public function withHeader(string $header, string $value): self
    {
        $this->options['headers'][$header] = $value;

        return $this;
    }

    final public function withHeaders(array $headers): self
    {
        $this->options['headers'] = $headers;

        return $this;
    }

    final public function withParameters(array $parameters): self
    {
        $this->options['parameters'] = $parameters;

        return $this;
    }

    final public function withServer(array $server): self
    {
        $this->options['server'] = $server;

        return $this;
    }

    final public function withFiles(array $files): self
    {
        $this->options['files'] = $files;

        return $this;
    }

    final public function withBody(?string $body): self
    {
        $this->options['body'] = $body;

        return $this;
    }

    final public function asJson($body = null): self
    {
        return $this->withBody(null !== $body ? \json_encode($body, JSON_THROW_ON_ERROR) : null)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json')
        ;
    }

    final public function asAjax(): self
    {
        return $this->withHeader('X-Requested-With', 'XMLHttpRequest');
    }

    /**
     * @internal
     */
    final public function parameters(): array
    {
        return $this->options['parameters'];
    }

    /**
     * @internal
     */
    final public function files(): array
    {
        return $this->options['files'];
    }

    /**
     * @author Kévin Dunglas <dunglas@gmail.com>
     *
     * @internal
     */
    final public function server(): array
    {
        $server = $this->options['server'];

        foreach ($this->options['headers'] as $header => $value) {
            $header = \mb_strtoupper(\str_replace('-', '_', $header));

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
        return $this->options['body'];
    }
}

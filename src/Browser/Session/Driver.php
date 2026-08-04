<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Session;

use Behat\Mink\Driver\CoreDriver;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Assert;
use Zenstruck\Browser\HttpOptions;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
abstract class Driver extends CoreDriver
{
    /** @var AbstractBrowser<Request, Response> */
    private AbstractBrowser $client;
    private bool $started = false;

    /** @var mixed */
    private $expectedException;
    private ?string $expectedExceptionMessage = null;
    private bool $catchExceptionsEnabled = true;

    /**
     * @param AbstractBrowser<Request, Response> $client
     */
    public function __construct(AbstractBrowser $client)
    {
        $this->client = $client;
    }

    /**
     * @return AbstractBrowser<Request, Response>
     */
    public function client(): AbstractBrowser
    {
        return $this->client;
    }

    public function start(): void
    {
        $this->started = true;
    }

    public function isStarted(): bool
    {
        return $this->started;
    }

    public function stop(): void
    {
        $this->started = false;
    }

    public function reset(): void
    {
        $this->client()->restart();
    }

    public function quit(): void
    {
    }

    /**
     * The response as the kernel produced it, for drivers that render it before exposing it.
     */
    public function rawContent(): string
    {
        return $this->getContent();
    }

    /**
     * @param class-string|callable $expectedException
     */
    public function expectException($expectedException, ?string $expectedMessage = null): void
    {
        $this->expectedException = $expectedException;
        $this->expectedExceptionMessage = $expectedMessage;
    }

    public function catchExceptions(bool $catch): void
    {
        $this->clientCatchExceptions($catch);

        $this->catchExceptionsEnabled = $catch;
    }

    abstract public function request(string $method, string $url, HttpOptions $options): void;

    /**
     * Tell the client to stop, or resume, converting kernel exceptions into responses.
     */
    abstract protected function clientCatchExceptions(bool $catch): void;

    final protected function wrapRequest(callable $callback): void
    {
        if (!$this->expectedException) {
            $callback();

            return;
        }

        $this->clientCatchExceptions(false);

        try {
            Assert::that($callback)->throws($this->expectedException, $this->expectedExceptionMessage);
        } finally {
            // a request the browser has not finished can be handled after this one returns, so
            // leaving catching disabled would throw the same exception again, out of a later call
            $this->clientCatchExceptions($this->catchExceptionsEnabled);

            $this->expectedException = $this->expectedExceptionMessage = null;
        }
    }
}

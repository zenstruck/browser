<?php

namespace Zenstruck\Browser\Session;

use Behat\Mink\Driver\CoreDriver;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Zenstruck\Browser\HttpOptions;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
abstract class Driver extends CoreDriver
{
    private AbstractBrowser $client;
    private bool $started = false;

    public function __construct(AbstractBrowser $client)
    {
        $this->client = $client;
    }

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

    abstract public function request(string $method, string $url, HttpOptions $options): void;
}

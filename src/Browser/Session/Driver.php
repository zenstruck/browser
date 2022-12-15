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
use Behat\Mink\Exception\UnsupportedDriverActionException;
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

    /**
     * @param class-string|callable $expectedException
     */
    public function expectException($expectedException, ?string $expectedMessage = null): void
    {
        throw new UnsupportedDriverActionException('%s does not support expecting exceptions.', $this);
    }

    abstract public function request(string $method, string $url, HttpOptions $options): void;
}

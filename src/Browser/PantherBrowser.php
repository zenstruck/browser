<?php

namespace Zenstruck\Browser;

use Symfony\Component\Panther\Client;
use Zenstruck\Browser;
use Zenstruck\Browser\Mink\PantherDriver;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class PantherBrowser extends Browser
{
    private Client $client;

    final public function __construct(Client $client)
    {
        parent::__construct(new PantherDriver($this->client = $client));
    }

    public function client(): Client
    {
        return $this->client;
    }

    /**
     * @return static
     */
    public function wait(int $milliseconds): self
    {
        \usleep($milliseconds * 1000);

        return $this;
    }

    public function waitUntilVisible(string $selector): self
    {
        $this->client->waitForVisibility($selector);

        return $this;
    }

    public function waitUntilHidden(string $selector): self
    {
        $this->client->waitForInvisibility($selector);

        return $this;
    }

    public function waitUntilSeeIn(string $selector, string $expected): self
    {
        $this->client->waitForElementToContain($selector, $expected);

        return $this;
    }

    public function waitUntilNotSeeIn(string $selector, string $expected): self
    {
        $this->client->waitForElementToNotContain($selector, $expected);

        return $this;
    }

    /**
     * @return static
     */
    final public function inspect(): self
    {
        if (!($_SERVER['PANTHER_NO_HEADLESS'] ?? false)) {
            throw new \RuntimeException('The "PANTHER_NO_HEADLESS" env variable must be set to inspect.');
        }

        \fwrite(STDIN, "\n\nInspecting the browser.\n\nPress enter to continue...");
        \fgets(STDIN);

        return $this;
    }

    /**
     * @return static
     */
    final public function takeScreenshot(string $filename): self
    {
        $this->client->takeScreenshot($filename);

        return $this;
    }
}

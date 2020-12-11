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
    final public function inspect(): self
    {
        if (!($_SERVER['PANTHER_NO_HEADLESS'] ?? false)) {
            throw new \RuntimeException('The "PANTHER_NO_HEADLESS" env variable must be set to inspect.');
        }

        \fwrite(STDIN, "\n\nInspecting the browser.\n\nPress enter to continue...");
        \fgets(STDIN);

        return $this;
    }

    final public function takeScreenshot(string $filename): self
    {
        $this->client->takeScreenshot($filename);

        return $this;
    }
}

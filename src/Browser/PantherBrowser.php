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
    final public function __construct(Client $client)
    {
        parent::__construct(new PantherDriver($client));
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
}

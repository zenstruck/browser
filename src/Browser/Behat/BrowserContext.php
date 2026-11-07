<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Behat;

use Behat\MinkExtension\Context\RawMinkContext;
use Zenstruck\Browser\Behat\Driver\KernelDriver;
use Zenstruck\Browser\Behat\Driver\PlaywrightDriver;
use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Browser\PlaywrightBrowser;

/**
 * @experimental
 */
abstract class BrowserContext extends RawMinkContext
{
    protected function browser(): KernelBrowser
    {
        $session = $this->getSession();
        $driver = $session->getDriver();

        if (!$driver instanceof KernelDriver) {
            throw new \LogicException('The active Mink session must use the "zenstruck_kernel" driver to call browser().');
        }

        return $driver->browser();
    }

    protected function playwrightBrowser(): PlaywrightBrowser
    {
        $session = $this->getSession();
        $driver = $session->getDriver();

        if (!$driver instanceof PlaywrightDriver) {
            throw new \LogicException('The active Mink session must use the "zenstruck_playwright" driver to call playwrightBrowser().');
        }

        return $driver->browser();
    }
}

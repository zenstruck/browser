<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Test\Behat;

use Behat\Mink\Mink as BaseMink;
use Behat\Mink\Session;
use Behat\Mink\WebAssert;
use Zenstruck\Browser\Test\Behat\Driver\PlaywrightDriver;

/**
 * @internal
 */
final class Mink extends BaseMink
{
    public function assertSession($session = null): WebAssert
    {
        $session = $session instanceof Session ? $session : $this->getSession($session);
        $driver = $session->getDriver();

        if ($driver instanceof PlaywrightDriver) {
            return new PlaywrightWebAssert($session, $driver);
        }

        return parent::assertSession($session);
    }
}

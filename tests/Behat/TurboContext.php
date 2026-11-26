<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\Hook\AfterScenario;
use Behat\Hook\BeforeScenario;

final class TurboContext implements Context
{
    #[BeforeScenario]
    public function enableTurbo(): void
    {
        $_SERVER['TURBO'] = '1';
    }

    #[AfterScenario]
    public function disableTurbo(): void
    {
        unset($_SERVER['TURBO']);
    }
}

<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Test;

use PHPUnit\Runner\AfterLastTestHook;
use PHPUnit\Runner\AfterTestErrorHook;
use PHPUnit\Runner\AfterTestFailureHook;
use PHPUnit\Runner\AfterTestHook;
use PHPUnit\Runner\BeforeFirstTestHook;
use PHPUnit\Runner\BeforeTestHook;
use PHPUnit\Runner\Extension\Extension;

if (\interface_exists(Extension::class)) {
    /**
     * PHPUnit >= 10.
     */
    final class BrowserExtension extends BootstrappedExtension implements Extension
    {
    }
} else {
    /**
     * PHPUnit < 10.
     */
    final class BrowserExtension extends LegacyExtension implements BeforeFirstTestHook, BeforeTestHook, AfterTestHook, AfterLastTestHook, AfterTestErrorHook, AfterTestFailureHook
    {
    }
}

<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Bridge\Behat\Context;

use Zenstruck\Browser\BrowserFactory;
use Zenstruck\Browser\BrowserRegistry;
use Zenstruck\Browser\KernelBooter;

/**
 * Implemented by Behat contexts that want a {@see \Zenstruck\Browser\KernelBrowser}
 * or {@see \Zenstruck\Browser\PantherBrowser} injected. {@see BrowserAwareTrait}
 * provides the default implementation.
 *
 * @author Hugo Hamon <hello@kodero.fr>
 */
interface BrowserAware
{
    public function setBrowserServices(BrowserFactory $factory, BrowserRegistry $registry, KernelBooter $booter): void;
}

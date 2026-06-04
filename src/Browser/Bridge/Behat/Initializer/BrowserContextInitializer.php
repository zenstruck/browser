<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Bridge\Behat\Initializer;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use Zenstruck\Browser\Bridge\Behat\Context\BrowserAware;
use Zenstruck\Browser\BrowserFactory;
use Zenstruck\Browser\BrowserRegistry;
use Zenstruck\Browser\KernelBooter;

/**
 * Injects the browser services into any Behat context that implements
 * {@see BrowserAware}.
 *
 * @author Hugo Hamon <hello@kodero.fr>
 */
final class BrowserContextInitializer implements ContextInitializer
{
    public function __construct(
        private readonly BrowserFactory $factory,
        private readonly BrowserRegistry $registry,
        private readonly KernelBooter $booter,
    ) {
    }

    public function initializeContext(Context $context): void
    {
        if (!$context instanceof BrowserAware) {
            return;
        }

        $context->setBrowserServices($this->factory, $this->registry, $this->booter);
    }
}

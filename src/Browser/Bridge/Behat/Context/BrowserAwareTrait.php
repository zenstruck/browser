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
use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Browser\PantherBrowser;

/**
 * Behat-side analog of {@see \Zenstruck\Browser\Test\HasBrowser}. Provides the
 * same `browser()` / `pantherBrowser()` API but consumes services injected by
 * the {@see \Zenstruck\Browser\Bridge\Behat\Initializer\BrowserContextInitializer}.
 *
 * @author Hugo Hamon <hello@kodero.fr>
 */
trait BrowserAwareTrait
{
    private BrowserFactory $browserFactory;
    private BrowserRegistry $browserRegistry;
    private KernelBooter $browserKernelBooter;

    public function setBrowserServices(BrowserFactory $factory, BrowserRegistry $registry, KernelBooter $booter): void
    {
        $this->browserFactory = $factory;
        $this->browserRegistry = $registry;
        $this->browserKernelBooter = $booter;
    }

    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $server
     */
    protected function browser(array $options = [], array $server = []): KernelBrowser
    {
        return $this->browserFactory->createKernelBrowser($options, $server);
    }

    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $kernelOptions
     * @param array<string, mixed> $managerOptions
     */
    protected function pantherBrowser(array $options = [], array $kernelOptions = [], array $managerOptions = []): PantherBrowser
    {
        return $this->browserFactory->createPantherBrowser($options, $kernelOptions, $managerOptions);
    }
}

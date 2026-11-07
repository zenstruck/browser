<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Behat\Driver;

use Behat\Mink\Driver\BrowserKitDriver;
use Symfony\Bundle\FrameworkBundle\KernelBrowser as SymfonyKernelBrowser;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Browser\KernelBrowser;

/**
 * @internal
 *
 * @extends BrowserKitDriver<\Symfony\Component\HttpFoundation\Request, \Symfony\Component\HttpFoundation\Response>
 */
final class KernelDriver extends BrowserKitDriver
{
    private KernelBrowser $browser;

    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly ?string $baseUrl = null,
    ) {
        parent::__construct($this->createClient(), $this->baseUrl);
    }

    public function browser(): KernelBrowser
    {
        if (!$this->isStarted()) {
            $this->start();
        }

        return $this->browser;
    }

    public function reset(): void
    {
        parent::reset();

        // SymfonyExtension reboots the kernel between scenarios, so its test client must be resolved again.
        parent::__construct($this->createClient(), $this->baseUrl);
    }

    private function createClient(): SymfonyKernelBrowser
    {
        $container = $this->kernel->getContainer();

        if (!$container->has('test.client')) {
            throw new \LogicException('Enable "framework.test" to use Browser with Behat.');
        }

        $client = $container->get('test.client');

        if (!$client instanceof SymfonyKernelBrowser) {
            throw new \LogicException('The "test.client" service must be a Symfony KernelBrowser.');
        }

        $this->browser = new KernelBrowser($client);

        return $this->browser->client();
    }
}

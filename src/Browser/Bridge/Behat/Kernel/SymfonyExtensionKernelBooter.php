<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Bridge\Behat\Kernel;

use Symfony\Bundle\FrameworkBundle\KernelBrowser as SymfonyKernelBrowser;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Panther\Client as PantherClient;
use Symfony\Component\Panther\PantherTestCase;
use Zenstruck\Browser\KernelBooter;

/**
 * Booter that consumes the Symfony kernel managed by
 * `friends-of-behat/symfony-extension`. The kernel is rebooted between
 * scenarios so each one gets a fresh container.
 *
 * @author Hugo Hamon <hello@kodero.fr>
 */
final class SymfonyExtensionKernelBooter implements KernelBooter
{
    private ?PantherClient $primaryPantherClient = null;

    public function __construct(
        private readonly KernelInterface $kernel,
    ) {
    }

    public function createKernelBrowserClient(array $options = [], array $server = []): SymfonyKernelBrowser
    {
        if (!$this->kernel->getContainer()->has('test.client')) {
            $this->kernel->boot();
        }

        $container = $this->kernel->getContainer();

        if (!$container->has('test.client')) {
            throw new \RuntimeException('The Symfony test client is not enabled. Enable framework.test in your test config.');
        }

        $client = $container->get('test.client');
        \assert($client instanceof SymfonyKernelBrowser);
        $client->setServerParameters($server);

        return $client;
    }

    public function createPantherClient(array $options = [], array $kernelOptions = [], array $managerOptions = []): PantherClient
    {
        if (!\class_exists(PantherTestCase::class)) {
            throw new \LogicException('symfony/panther must be installed to use the PantherBrowser.');
        }

        if ($this->primaryPantherClient instanceof PantherClient) {
            return PantherClientFactory::createAdditional();
        }

        return $this->primaryPantherClient = PantherClientFactory::createPrimary($options, $kernelOptions, $managerOptions);
    }

    public function supportsPanther(): bool
    {
        return \class_exists(PantherTestCase::class);
    }

    public function reset(): void
    {
        $this->kernel->shutdown();
        $this->primaryPantherClient = null;
    }
}

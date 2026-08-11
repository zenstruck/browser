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
 * Boots the Symfony kernel directly from env vars (KERNEL_CLASS, APP_ENV,
 * APP_DEBUG) — used when `friends-of-behat/symfony-extension` is not installed
 * or not enabled. The kernel is rebuilt between scenarios via {@see reset()}.
 *
 * @author Hugo Hamon <hello@kodero.fr>
 */
final class StandaloneKernelBooter implements KernelBooter
{
    /** @var class-string<KernelInterface> */
    private readonly string $kernelClass;

    private ?KernelInterface $kernel = null;
    private ?PantherClient $primaryPantherClient = null;

    public function __construct(
        ?string $kernelClass = null,
        private readonly string $environment = 'test',
        private readonly bool $debug = true,
    ) {
        $kernelClass ??= $_SERVER['KERNEL_CLASS'] ?? null;

        if (!\is_string($kernelClass) || !\is_a($kernelClass, KernelInterface::class, true)) {
            throw new \RuntimeException(\sprintf('The "kernel_class" option (or KERNEL_CLASS env var) must reference a class that implements %s.', KernelInterface::class));
        }

        $this->kernelClass = $kernelClass;
    }

    public function createKernelBrowserClient(array $options = [], array $server = []): SymfonyKernelBrowser
    {
        $kernel = $this->bootKernel();

        $container = $kernel->getContainer();

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
        if ($this->kernel instanceof KernelInterface) {
            $this->kernel->shutdown();
            $this->kernel = null;
        }

        $this->primaryPantherClient = null;
    }

    private function bootKernel(): KernelInterface
    {
        if ($this->kernel instanceof KernelInterface) {
            return $this->kernel;
        }

        $kernel = new $this->kernelClass($this->environment, $this->debug);
        $kernel->boot();

        return $this->kernel = $kernel;
    }
}

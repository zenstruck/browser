<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser;

use Symfony\Component\Panther\Client as PantherClient;
use Symfony\Component\Panther\PantherTestCase;

/**
 * Test-framework-agnostic factory that turns a {@see KernelBooter} into ready-to-use
 * {@see KernelBrowser} and {@see PantherBrowser} instances, registering each
 * created browser into the {@see BrowserRegistry} so the artifact-capture
 * lifecycle can dump their state on failure.
 *
 * @author Hugo Hamon <hello@kodero.fr>
 */
final class BrowserFactory
{
    public function __construct(
        private readonly KernelBooter $kernelBooter,
        private readonly BrowserRegistry $registry,
        private readonly BrowserOptions $options,
    ) {
    }

    /**
     * @param array<string, mixed> $options Kernel boot options
     * @param array<string, mixed> $server  Server parameters
     */
    public function createKernelBrowser(array $options = [], array $server = []): KernelBrowser
    {
        $class = $this->options->kernelBrowserClass ?? KernelBrowser::class;

        if (!\is_a($class, KernelBrowser::class, true)) {
            throw new \LogicException(\sprintf('"KERNEL_BROWSER_CLASS" env variable must reference a class that extends %s.', KernelBrowser::class));
        }

        $client = $this->kernelBooter->createKernelBrowserClient($options, $server);

        $browser = new $class($client, $this->options->toKernelBrowserOptions());

        $this->registry->register($browser);

        return $browser;
    }

    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $kernelOptions
     * @param array<string, mixed> $managerOptions
     */
    public function createPantherBrowser(array $options = [], array $kernelOptions = [], array $managerOptions = []): PantherBrowser
    {
        if (!\class_exists(PantherClient::class)) {
            throw new \LogicException('symfony/panther must be installed to use the PantherBrowser (composer require symfony/panther).');
        }

        $class = $this->options->pantherBrowserClass ?? PantherBrowser::class;

        if (!\is_a($class, PantherBrowser::class, true)) {
            throw new \LogicException(\sprintf('"PANTHER_BROWSER_CLASS" env variable must reference a class that extends %s.', PantherBrowser::class));
        }

        if ($this->options->alwaysStartWebserver) {
            $_SERVER['PANTHER_APP_ENV'] = $_SERVER['APP_ENV'] ?? 'test';
            $_SERVER['SYMFONY_PROJECT_DEFAULT_ROUTE_URL'] = '';
        }

        $clientOptions = \array_merge(
            ['browser' => $this->options->pantherBrowser ?? PantherTestCase::CHROME],
            $options,
        );

        $client = $this->kernelBooter->createPantherClient($clientOptions, $kernelOptions, $managerOptions);

        $browser = new $class($client, $this->options->toPantherBrowserOptions());

        $this->registry->register($browser);

        return $browser;
    }
}

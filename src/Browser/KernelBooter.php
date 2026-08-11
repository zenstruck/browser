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

use Symfony\Bundle\FrameworkBundle\KernelBrowser as SymfonyKernelBrowser;
use Symfony\Component\Panther\Client as PantherClient;

/**
 * Abstracts how the underlying Symfony kernel is booted to obtain a test client
 * for the current test or scenario. Decouples {@see BrowserFactory} from any
 * particular test-runner (PHPUnit, Behat, ...).
 *
 * @author Hugo Hamon <hello@kodero.fr>
 */
interface KernelBooter
{
    /**
     * @param array<string, mixed> $options Kernel boot options
     * @param array<string, mixed> $server  Server parameters (REMOTE_ADDR, HTTPS, ...)
     */
    public function createKernelBrowserClient(array $options = [], array $server = []): SymfonyKernelBrowser;

    /**
     * Returns the primary Panther client the first time it is called, and an
     * additional client on subsequent calls within the same test or scenario.
     *
     * @param array<string, mixed> $options
     * @param array<string, mixed> $kernelOptions
     * @param array<string, mixed> $managerOptions
     */
    public function createPantherClient(array $options = [], array $kernelOptions = [], array $managerOptions = []): PantherClient;

    public function supportsPanther(): bool;

    /**
     * Releases any kernel/Panther state held between tests or scenarios.
     */
    public function reset(): void;
}

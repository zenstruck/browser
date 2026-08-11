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

use Symfony\Bundle\FrameworkBundle\KernelBrowser as SymfonyKernelBrowser;
use Symfony\Component\Panther\Client as PantherClient;
use Symfony\Component\Panther\PantherTestCase;
use Symfony\Component\Panther\PantherTestCaseTrait;
use Zenstruck\Browser\KernelBooter;

/**
 * {@see KernelBooter} fed by closures captured inside the {@see HasBrowser}
 * trait. The closures live in the trait's host-class scope so they can reach
 * the protected static methods on {@see \Symfony\Bundle\FrameworkBundle\Test\KernelTestCase}
 * and {@see PantherTestCase} (which cannot be invoked from a foreign class).
 *
 * @author Hugo Hamon <hello@kodero.fr>
 */
final class PhpUnitKernelBooter implements KernelBooter
{
    private ?PantherClient $primaryPantherClient = null;

    /**
     * @param \Closure(array<string, mixed>, array<string, mixed>): SymfonyKernelBrowser $createKernelBrowserClient
     * @param ?\Closure(array<string, mixed>, array<string, mixed>, array<string, mixed>): PantherClient $createPantherClient
     * @param ?\Closure(): PantherClient $createAdditionalPantherClient
     */
    public function __construct(
        private readonly \Closure $createKernelBrowserClient,
        private readonly ?\Closure $createPantherClient = null,
        private readonly ?\Closure $createAdditionalPantherClient = null,
    ) {
    }

    public function createKernelBrowserClient(array $options = [], array $server = []): SymfonyKernelBrowser
    {
        return ($this->createKernelBrowserClient)($options, $server);
    }

    public function createPantherClient(array $options = [], array $kernelOptions = [], array $managerOptions = []): PantherClient
    {
        if ($this->createPantherClient === null) {
            throw new \LogicException(\sprintf('A PantherBrowser can only be created in TestCases that extend "%s" or use "%s".', PantherTestCase::class, PantherTestCaseTrait::class));
        }

        if ($this->primaryPantherClient instanceof PantherClient) {
            \assert($this->createAdditionalPantherClient !== null);

            return ($this->createAdditionalPantherClient)();
        }

        return $this->primaryPantherClient = ($this->createPantherClient)($options, $kernelOptions, $managerOptions);
    }

    public function supportsPanther(): bool
    {
        return $this->createPantherClient !== null;
    }

    public function reset(): void
    {
        $this->primaryPantherClient = null;
    }
}

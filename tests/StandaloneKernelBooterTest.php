<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser as SymfonyKernelBrowser;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Browser\Bridge\Behat\Kernel\StandaloneKernelBooter;
use Zenstruck\Browser\KernelBooter;
use Zenstruck\Browser\Tests\Fixture\Kernel;

/**
 * @author Hugo Hamon <hello@kodero.fr>
 */
final class StandaloneKernelBooterTest extends TestCase
{
    /**
     * @test
     */
    public function implements_kernel_booter(): void
    {
        $this->assertInstanceOf(KernelBooter::class, new StandaloneKernelBooter(kernelClass: Kernel::class));
    }

    /**
     * @test
     */
    public function constructor_uses_given_kernel_class(): void
    {
        $booter = new StandaloneKernelBooter(kernelClass: Kernel::class);

        $this->assertTrue($booter->supportsPanther());
    }

    /**
     * @test
     */
    public function constructor_uses_env_var_when_no_class_given(): void
    {
        $_SERVER['KERNEL_CLASS'] = Kernel::class;

        try {
            $booter = new StandaloneKernelBooter();
            $this->assertTrue($booter->supportsPanther());
        } finally {
            unset($_SERVER['KERNEL_CLASS']);
        }
    }

    /**
     * @test
     */
    public function constructor_throws_when_no_kernel_class(): void
    {
        unset($_SERVER['KERNEL_CLASS']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('kernel_class');

        new StandaloneKernelBooter();
    }

    /**
     * @test
     */
    public function constructor_throws_when_class_does_not_implement_kernel_interface(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('KernelInterface');

        new StandaloneKernelBooter(kernelClass: self::class);
    }

    /**
     * @test
     */
    public function constructor_throws_when_class_does_not_exist(): void
    {
        $this->expectException(\RuntimeException::class);

        new StandaloneKernelBooter(kernelClass: 'NonExistentKernelClass');
    }

    /**
     * @test
     */
    public function createKernelBrowserClient_returns_client_from_booted_kernel(): void
    {
        $booter = new StandaloneKernelBooter(kernelClass: Kernel::class);

        $client = $booter->createKernelBrowserClient();

        $this->assertInstanceOf(SymfonyKernelBrowser::class, $client);
    }

    /**
     * @test
     */
    public function createKernelBrowserClient_sets_server_parameters(): void
    {
        $booter = new StandaloneKernelBooter(kernelClass: Kernel::class);

        $client = $booter->createKernelBrowserClient(server: ['REMOTE_ADDR' => '10.0.0.1']);

        $this->assertInstanceOf(SymfonyKernelBrowser::class, $client);
    }

    /**
     * @test
     */
    public function createKernelBrowserClient_uses_cached_kernel_on_subsequent_calls(): void
    {
        $booter = new StandaloneKernelBooter(kernelClass: Kernel::class);

        $first = $booter->createKernelBrowserClient();
        $second = $booter->createKernelBrowserClient();

        $this->assertInstanceOf(SymfonyKernelBrowser::class, $first);
        $this->assertInstanceOf(SymfonyKernelBrowser::class, $second);
    }

    /**
     * @test
     */
    public function supportsPanther_returns_true_when_panther_installed(): void
    {
        $booter = new StandaloneKernelBooter(kernelClass: Kernel::class);

        $this->assertTrue($booter->supportsPanther());
    }

    /**
     * @test
     */
    public function reset_shuts_down_kernel(): void
    {
        $booter = new StandaloneKernelBooter(kernelClass: Kernel::class);
        $booter->createKernelBrowserClient();

        $booter->reset();

        // After reset, the next call should create a new client
        $client = $booter->createKernelBrowserClient();
        $this->assertInstanceOf(SymfonyKernelBrowser::class, $client);
    }

    /**
     * @test
     */
    public function reset_without_booted_kernel_does_not_fail(): void
    {
        $booter = new StandaloneKernelBooter(kernelClass: Kernel::class);

        $booter->reset();

        $this->expectNotToPerformAssertions();
    }
}

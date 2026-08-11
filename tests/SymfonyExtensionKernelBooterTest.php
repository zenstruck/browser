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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser as SymfonyKernelBrowser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Browser\Bridge\Behat\Kernel\SymfonyExtensionKernelBooter;
use Zenstruck\Browser\KernelBooter;

/**
 * @author Hugo Hamon <hello@kodero.fr>
 */
final class SymfonyExtensionKernelBooterTest extends TestCase
{
    private KernelInterface&MockObject $kernel;
    private SymfonyExtensionKernelBooter $booter;

    protected function setUp(): void
    {
        $this->kernel = $this->createMock(KernelInterface::class);
        $this->booter = new SymfonyExtensionKernelBooter($this->kernel);
    }

    /**
     * @test
     */
    public function implements_kernel_booter(): void
    {
        $this->assertInstanceOf(KernelBooter::class, $this->booter);
    }

    /**
     * @test
     */
    public function createKernelBrowserClient_returns_client_from_container(): void
    {
        $client = $this->createMock(SymfonyKernelBrowser::class);

        $container = $this->createContainerWithTestClient($client);
        $this->kernel
            ->expects($this->exactly(2))
            ->method('getContainer')
            ->willReturn($container)
        ;
        $this->kernel->expects($this->never())->method('boot');

        $this->assertSame($client, $this->booter->createKernelBrowserClient());
    }

    /**
     * @test
     */
    public function createKernelBrowserClient_boots_kernel_when_container_lacks_client(): void
    {
        $client = $this->createMock(SymfonyKernelBrowser::class);

        $firstContainer = $this->createContainerWithoutTestClient();
        $secondContainer = $this->createContainerWithTestClient($client);

        $this->kernel
            ->expects($this->exactly(2))
            ->method('getContainer')
            ->willReturnOnConsecutiveCalls($firstContainer, $secondContainer)
        ;
        $this->kernel->expects($this->once())->method('boot');

        $this->assertSame($client, $this->booter->createKernelBrowserClient());
    }

    /**
     * @test
     */
    public function createKernelBrowserClient_sets_server_parameters(): void
    {
        $client = $this->createMock(SymfonyKernelBrowser::class);
        $client->expects($this->once())
            ->method('setServerParameters')
            ->with($this->identicalTo(['REMOTE_ADDR' => '10.0.0.1']))
        ;

        $container = $this->createContainerWithTestClient($client);
        $this->kernel->method('getContainer')->willReturn($container);

        $this->booter->createKernelBrowserClient(server: ['REMOTE_ADDR' => '10.0.0.1']);
    }

    /**
     * @test
     */
    public function createKernelBrowserClient_uses_empty_server_parameters_by_default(): void
    {
        $client = $this->createMock(SymfonyKernelBrowser::class);
        $client->expects($this->once())
            ->method('setServerParameters')
            ->with($this->identicalTo([]))
        ;

        $container = $this->createContainerWithTestClient($client);
        $this->kernel->method('getContainer')->willReturn($container);

        $this->booter->createKernelBrowserClient();
    }

    /**
     * @test
     */
    public function createKernelBrowserClient_throws_when_test_client_not_enabled(): void
    {
        $container = $this->createContainerWithoutTestClient();

        $this->kernel
            ->expects($this->exactly(2))
            ->method('getContainer')
            ->willReturnOnConsecutiveCalls($container, $container)
        ;
        $this->kernel->expects($this->once())->method('boot');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The Symfony test client is not enabled');

        $this->booter->createKernelBrowserClient();
    }

    /**
     * @test
     */
    public function supportsPanther_returns_true_when_panther_installed(): void
    {
        $this->assertTrue($this->booter->supportsPanther());
    }

    /**
     * @test
     */
    public function reset_shuts_down_kernel(): void
    {
        $this->kernel->expects($this->once())->method('shutdown');

        $this->booter->reset();
    }

    /**
     * @return ContainerInterface&MockObject
     */
    private function createContainerWithTestClient(SymfonyKernelBrowser $client): MockObject
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('test.client')->willReturn(true);
        $container->method('get')->with('test.client')->willReturn($client);

        return $container;
    }

    /**
     * @return ContainerInterface&MockObject
     */
    private function createContainerWithoutTestClient(): MockObject
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('test.client')->willReturn(false);

        return $container;
    }
}

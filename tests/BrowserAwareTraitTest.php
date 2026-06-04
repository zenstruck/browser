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
use Symfony\Component\Panther\Client as PantherClient;
use Symfony\Component\Panther\ProcessManager\BrowserManagerInterface;
use Zenstruck\Browser\Bridge\Behat\Context\BrowserAware;
use Zenstruck\Browser\Bridge\Behat\Context\BrowserAwareTrait;
use Zenstruck\Browser\BrowserFactory;
use Zenstruck\Browser\BrowserOptions;
use Zenstruck\Browser\BrowserRegistry;
use Zenstruck\Browser\KernelBooter;
use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Browser\PantherBrowser;

final class BrowserAwareContext implements BrowserAware
{
    use BrowserAwareTrait;

    public function callBrowser(array $options = [], array $server = []): KernelBrowser
    {
        return $this->browser($options, $server);
    }

    public function callPantherBrowser(array $options = [], array $kernelOptions = [], array $managerOptions = []): PantherBrowser
    {
        return $this->pantherBrowser($options, $kernelOptions, $managerOptions);
    }
}

/**
 * @author Hugo Hamon <hello@kodero.fr>
 */
final class BrowserAwareTraitTest extends TestCase
{
    private BrowserRegistry $registry;
    private KernelBooter& \PHPUnit\Framework\MockObject\MockObject $booter;
    private BrowserFactory $factory;
    private BrowserAwareContext $context;

    protected function setUp(): void
    {
        BrowserRegistry::resetDefault();
        $this->registry = new BrowserRegistry();
        $this->registry->start();
        $this->booter = $this->createMock(KernelBooter::class);
        $this->factory = new BrowserFactory($this->booter, $this->registry, new BrowserOptions());
        $this->context = new BrowserAwareContext();
        $this->context->setBrowserServices($this->factory, $this->registry, $this->booter);
    }

    protected function tearDown(): void
    {
        BrowserRegistry::resetDefault();
    }

    private function setPropertyAccessible(string $propertyName): \ReflectionProperty
    {
        return new \ReflectionProperty(BrowserAwareContext::class, $propertyName);
    }

    /**
     * @test
     */
    public function stores_browser_factory(): void
    {
        $this->assertSame(
            $this->factory,
            $this->setPropertyAccessible('browserFactory')->getValue($this->context),
        );
    }

    /**
     * @test
     */
    public function stores_browser_registry(): void
    {
        $this->assertSame(
            $this->registry,
            $this->setPropertyAccessible('browserRegistry')->getValue($this->context),
        );
    }

    /**
     * @test
     */
    public function stores_kernel_booter(): void
    {
        $this->assertSame(
            $this->booter,
            $this->setPropertyAccessible('browserKernelBooter')->getValue($this->context),
        );
    }

    /**
     * @test
     */
    public function browser_returns_kernel_browser_instance(): void
    {
        $client = $this->createMock(SymfonyKernelBrowser::class);
        $this->booter
            ->expects($this->once())
            ->method('createKernelBrowserClient')
            ->willReturn($client)
        ;

        $browser = $this->context->callBrowser();

        $this->assertInstanceOf(KernelBrowser::class, $browser);
    }

    /**
     * @test
     */
    public function browser_forwards_options_and_server_to_factory(): void
    {
        $client = $this->createMock(SymfonyKernelBrowser::class);
        $this->booter
            ->expects($this->once())
            ->method('createKernelBrowserClient')
            ->with(
                $this->identicalTo(['custom' => 'option']),
                $this->identicalTo(['REMOTE_ADDR' => '10.0.0.1']),
            )
            ->willReturn($client)
        ;

        $this->context->callBrowser(
            options: ['custom' => 'option'],
            server: ['REMOTE_ADDR' => '10.0.0.1'],
        );
    }

    /**
     * @test
     */
    public function browser_registers_instance_in_registry(): void
    {
        $client = $this->createMock(SymfonyKernelBrowser::class);
        $this->booter->method('createKernelBrowserClient')->willReturn($client);

        $browser = $this->context->callBrowser();

        $this->assertSame([$browser], $this->registry->all());
    }

    /**
     * @test
     */
    public function browser_uses_default_empty_options(): void
    {
        $client = $this->createMock(SymfonyKernelBrowser::class);
        $this->booter
            ->expects($this->once())
            ->method('createKernelBrowserClient')
            ->with([], [])
            ->willReturn($client)
        ;

        $this->context->callBrowser();
    }

    /**
     * @test
     */
    public function panther_browser_returns_panther_browser_instance(): void
    {
        $this->booter->method('createPantherClient')->willReturn($this->createPantherClient());

        $browser = $this->context->callPantherBrowser();

        $this->assertInstanceOf(PantherBrowser::class, $browser);
    }

    /**
     * @test
     */
    public function panther_browser_forwards_options_to_factory(): void
    {
        $this->booter
            ->expects($this->once())
            ->method('createPantherClient')
            ->with(
                $this->callback(fn(array $options): bool => isset($options['custom']) && 'value' === $options['custom']),
                $this->identicalTo(['kernel' => 'option']),
                $this->identicalTo(['manager' => 'option']),
            )
            ->willReturn($this->createPantherClient())
        ;

        $this->context->callPantherBrowser(
            options: ['custom' => 'value'],
            kernelOptions: ['kernel' => 'option'],
            managerOptions: ['manager' => 'option'],
        );
    }

    /**
     * @test
     */
    public function panther_browser_uses_default_empty_options(): void
    {
        $this->booter
            ->expects($this->once())
            ->method('createPantherClient')
            ->with(
                $this->callback(fn(array $options): bool => isset($options['browser'])),
                [],
                [],
            )
            ->willReturn($this->createPantherClient())
        ;

        $this->context->callPantherBrowser();
    }

    /**
     * @test
     */
    public function panther_browser_registers_instance_in_registry(): void
    {
        $this->booter->method('createPantherClient')->willReturn($this->createPantherClient());

        $browser = $this->context->callPantherBrowser();

        $this->assertSame([$browser], $this->registry->all());
    }

    /**
     * @test
     */
    public function throws_error_when_calling_browser_without_services(): void
    {
        $context = new BrowserAwareContext();

        $this->expectException(\Error::class);

        $context->callBrowser();
    }

    /**
     * @test
     */
    public function throws_error_when_calling_panther_browser_without_services(): void
    {
        $context = new BrowserAwareContext();

        $this->expectException(\Error::class);

        $context->callPantherBrowser();
    }

    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $kernelOptions
     * @param array<string, mixed> $managerOptions
     */
    private function createPantherClient(): PantherClient
    {
        $client = (new \ReflectionClass(PantherClient::class))->newInstanceWithoutConstructor();

        $managerProperty = new \ReflectionProperty(PantherClient::class, 'browserManager');
        $managerProperty->setValue($client, $this->createMock(BrowserManagerInterface::class));

        return $client;
    }
}

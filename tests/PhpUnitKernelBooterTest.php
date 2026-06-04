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
use Zenstruck\Browser\KernelBooter;
use Zenstruck\Browser\Test\PhpUnitKernelBooter;

/**
 * @author Hugo Hamon <hello@kodero.fr>
 */
final class PhpUnitKernelBooterTest extends TestCase
{
    /**
     * @test
     */
    public function implements_kernel_booter(): void
    {
        $this->assertInstanceOf(KernelBooter::class, new PhpUnitKernelBooter(fn() => $this->createMock(SymfonyKernelBrowser::class)));
    }

    /**
     * @test
     */
    public function createKernelBrowserClient_delegates_to_closure(): void
    {
        $expected = $this->createMock(SymfonyKernelBrowser::class);

        $booter = new PhpUnitKernelBooter(fn() => $expected);

        $this->assertSame($expected, $booter->createKernelBrowserClient());
    }

    /**
     * @test
     */
    public function createKernelBrowserClient_forwards_options_and_server(): void
    {
        $booter = new PhpUnitKernelBooter(
            createKernelBrowserClient: function(array $options, array $server): SymfonyKernelBrowser {
                $this->assertSame(['custom' => 'opt'], $options);
                $this->assertSame(['REMOTE_ADDR' => '10.0.0.1'], $server);

                return $this->createMock(SymfonyKernelBrowser::class);
            },
        );

        $booter->createKernelBrowserClient(options: ['custom' => 'opt'], server: ['REMOTE_ADDR' => '10.0.0.1']);
    }

    /**
     * @test
     */
    public function createKernelBrowserClient_uses_empty_defaults(): void
    {
        $booter = new PhpUnitKernelBooter(
            createKernelBrowserClient: function(array $options, array $server): SymfonyKernelBrowser {
                $this->assertSame([], $options);
                $this->assertSame([], $server);

                return $this->createMock(SymfonyKernelBrowser::class);
            },
        );

        $booter->createKernelBrowserClient();
    }

    /**
     * @test
     */
    public function createPantherClient_without_panther_closure_throws_logic_exception(): void
    {
        $booter = new PhpUnitKernelBooter(fn() => $this->createMock(SymfonyKernelBrowser::class));

        $this->expectException(\LogicException::class);

        $booter->createPantherClient();
    }

    /**
     * @test
     */
    public function createPantherClient_first_call_creates_and_caches(): void
    {
        $expected = $this->createPantherClient();

        $booter = new PhpUnitKernelBooter(
            createKernelBrowserClient: fn() => $this->createMock(SymfonyKernelBrowser::class),
            createPantherClient: fn() => $expected,
        );

        $this->assertSame($expected, $booter->createPantherClient());
    }

    /**
     * @test
     */
    public function createPantherClient_first_call_forwards_options(): void
    {
        $booter = new PhpUnitKernelBooter(
            createKernelBrowserClient: fn() => $this->createMock(SymfonyKernelBrowser::class),
            createPantherClient: function(array $options, array $kernelOptions, array $managerOptions): PantherClient {
                $this->assertSame(['browser' => 'chrome'], $options);
                $this->assertSame(['kernel' => 'opt'], $kernelOptions);
                $this->assertSame(['manager' => 'opt'], $managerOptions);

                return $this->createPantherClient();
            },
        );

        $booter->createPantherClient(
            options: ['browser' => 'chrome'],
            kernelOptions: ['kernel' => 'opt'],
            managerOptions: ['manager' => 'opt'],
        );
    }

    /**
     * @test
     */
    public function createPantherClient_uses_additional_closure_for_second_call(): void
    {
        $firstClient = $this->createPantherClient();
        $secondClient = $this->createPantherClient();

        $booter = new PhpUnitKernelBooter(
            createKernelBrowserClient: fn() => $this->createMock(SymfonyKernelBrowser::class),
            createPantherClient: fn() => $firstClient,
            createAdditionalPantherClient: fn() => $secondClient,
        );

        $this->assertSame($firstClient, $booter->createPantherClient());
        $this->assertSame($secondClient, $booter->createPantherClient());
    }

    /**
     * @test
     */
    public function createPantherClient_second_call_without_additional_closure_throws_error(): void
    {
        $booter = new PhpUnitKernelBooter(
            createKernelBrowserClient: fn() => $this->createMock(SymfonyKernelBrowser::class),
            createPantherClient: fn() => $this->createPantherClient(),
        );

        $booter->createPantherClient();

        $this->expectException(\Error::class);

        $booter->createPantherClient();
    }

    /**
     * @test
     */
    public function supportsPanther_returns_false_without_panther_closure(): void
    {
        $booter = new PhpUnitKernelBooter(fn() => $this->createMock(SymfonyKernelBrowser::class));

        $this->assertFalse($booter->supportsPanther());
    }

    /**
     * @test
     */
    public function supportsPanther_returns_true_with_panther_closure(): void
    {
        $booter = new PhpUnitKernelBooter(
            createKernelBrowserClient: fn() => $this->createMock(SymfonyKernelBrowser::class),
            createPantherClient: fn() => $this->createPantherClient(),
        );

        $this->assertTrue($booter->supportsPanther());
    }

    /**
     * @test
     */
    public function reset_creates_new_primary_client_on_next_call(): void
    {
        $booter = new PhpUnitKernelBooter(
            createKernelBrowserClient: fn() => $this->createMock(SymfonyKernelBrowser::class),
            createPantherClient: fn() => $this->createPantherClient(),
        );

        $first = $booter->createPantherClient();
        $booter->reset();
        $second = $booter->createPantherClient();

        $this->assertNotSame($first, $second);
    }

    private function createPantherClient(): PantherClient
    {
        $client = (new \ReflectionClass(PantherClient::class))->newInstanceWithoutConstructor();

        $managerProperty = new \ReflectionProperty(PantherClient::class, 'browserManager');
        $managerProperty->setValue($client, $this->createMock(BrowserManagerInterface::class));

        return $client;
    }
}

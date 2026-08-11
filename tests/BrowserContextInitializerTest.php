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

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use PHPUnit\Framework\TestCase;
use Zenstruck\Browser\Bridge\Behat\Context\BrowserAware;
use Zenstruck\Browser\Bridge\Behat\Initializer\BrowserContextInitializer;
use Zenstruck\Browser\BrowserFactory;
use Zenstruck\Browser\BrowserOptions;
use Zenstruck\Browser\BrowserRegistry;
use Zenstruck\Browser\KernelBooter;

/**
 * @author Hugo Hamon <hello@kodero.fr>
 */
final class BrowserContextInitializerTest extends TestCase
{
    /**
     * @test
     */
    public function implements_context_initializer_interface(): void
    {
        $this->assertInstanceOf(
            ContextInitializer::class,
            $this->createInitializer(),
        );
    }

    /**
     * @test
     */
    public function is_final_class(): void
    {
        $this->assertTrue(
            (new \ReflectionClass(BrowserContextInitializer::class))->isFinal(),
        );
    }

    /**
     * @test
     */
    public function does_nothing_when_context_is_not_browser_aware(): void
    {
        $this->createInitializer()->initializeContext(
            $this->createMock(Context::class),
        );

        $this->addToAssertionCount(1);
    }

    /**
     * @test
     */
    public function injects_browser_services_into_browser_aware_context(): void
    {
        if (!method_exists($this, 'createMockForIntersectionOfInterfaces')) {
            $this->markTestSkipped('This version of PHPUnit does not support `createMockForIntersectionOfInterfaces` method');
        }

        $factory = $this->createBrowserFactory();
        $registry = new BrowserRegistry();
        $booter = $this->createMock(KernelBooter::class);

        // The `createMockForIntersectionOfInterfaces` method is only available in PHPUnit 9.3+
        $context = $this->createMockForIntersectionOfInterfaces([Context::class, BrowserAware::class]);
        $context->expects($this->once())
            ->method('setBrowserServices')
            ->with($factory, $registry, $booter)
        ;

        $initializer = new BrowserContextInitializer($factory, $registry, $booter);
        $initializer->initializeContext($context);
    }

    /**
     * @test
     */
    public function constructor_parameters_are_promoted_correctly(): void
    {
        $reflection = new \ReflectionClass(BrowserContextInitializer::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);

        $params = $constructor->getParameters();

        $this->assertCount(3, $params);
        $this->assertSame('factory', $params[0]->getName());
        $this->assertSame(BrowserFactory::class, $params[0]->getType()->getName());
        $this->assertSame('registry', $params[1]->getName());
        $this->assertSame(BrowserRegistry::class, $params[1]->getType()->getName());
        $this->assertSame('booter', $params[2]->getName());
        $this->assertSame(KernelBooter::class, $params[2]->getType()->getName());
    }

    private function createInitializer(): BrowserContextInitializer
    {
        return new BrowserContextInitializer(
            $this->createBrowserFactory(),
            new BrowserRegistry(),
            $this->createMock(KernelBooter::class),
        );
    }

    private function createBrowserFactory(): BrowserFactory
    {
        return new BrowserFactory(
            $this->createMock(KernelBooter::class),
            new BrowserRegistry(),
            new BrowserOptions(),
        );
    }
}

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

use PHPUnit\Event\Code\Test;
use PHPUnit\Event\EventFacadeIsSealedException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use Zenstruck\Browser;
use Zenstruck\Browser\BrowserRegistry;
use Zenstruck\Browser\Test\BootstrappedExtension;

/**
 * @requires PHPUnit 10.0
 */
final class BootstrappedExtensionTest extends TestCase
{
    protected function setUp(): void
    {
        BrowserRegistry::resetDefault();
    }

    /**
     * @test
     */
    public function bootstrap_resets_browser_registry(): void
    {
        $original = BrowserRegistry::default();

        $extension = new BootstrappedExtension();

        try {
            $extension->bootstrap(
                (new \ReflectionClass(Configuration::class))->newInstanceWithoutConstructor(),
                (new \ReflectionClass(Facade::class))->newInstanceWithoutConstructor(),
                ParameterCollection::fromArray([]),
            );
        } catch (EventFacadeIsSealedException) {
            // The EventFacade is sealed during test execution, which prevents
            // subscriber registration. BrowserRegistry::resetDefault() is
            // called before that, so we can still verify the reset below.
        }

        $this->assertNotSame($original, BrowserRegistry::default());
    }

    /**
     * @test
     */
    public function test_name_returns_name_with_class_for_test_methods(): void
    {
        $test = new class('/tmp/file.php') extends Test {
            public function id(): string
            {
                return 'Zenstruck\Tests\FooTest::test_bar';
            }

            public function name(): string
            {
                return 'test_bar';
            }

            public function nameWithClass(): string
            {
                return 'Zenstruck\Tests\FooTest::test_bar';
            }

            public function isTestMethod(): bool
            {
                return true;
            }
        };

        $this->assertSame(
            'Zenstruck\Tests\FooTest::test_bar',
            BootstrappedExtension::testName($test),
        );
    }

    /**
     * @test
     */
    public function test_name_returns_name_for_non_test_methods(): void
    {
        $test = $this->createMock(Test::class);
        $test->method('isTestMethod')->willReturn(false);
        $test->method('name')->willReturn('test_bar');

        $this->assertSame('test_bar', BootstrappedExtension::testName($test));
    }

    /**
     * @test
     */
    public function register_browser_delegates_to_browser_registry(): void
    {
        $browser = $this->createMock(Browser::class);

        BrowserRegistry::default()->start();
        BootstrappedExtension::registerBrowser($browser);

        $this->assertSame([$browser], BrowserRegistry::default()->all());
    }
}

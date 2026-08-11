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
use Zenstruck\Browser;
use Zenstruck\Browser\BrowserRegistry;
use Zenstruck\Browser\Test\LegacyExtension;

/**
 * @author Hugo Hamon <hello@kodero.fr>
 */
final class LegacyExtensionTest extends TestCase
{
    protected function setUp(): void
    {
        BrowserRegistry::resetDefault();
    }

    protected function tearDown(): void
    {
        BrowserRegistry::resetDefault();
    }

    /**
     * @test
     */
    public function execute_before_first_test_starts_registry(): void
    {
        $extension = new LegacyExtension();
        $extension->executeBeforeFirstTest();

        $this->assertTrue(BrowserRegistry::default()->isStarted());
    }

    /**
     * @test
     */
    public function execute_before_test_clears_registry(): void
    {
        $extension = new LegacyExtension();
        $extension->executeBeforeFirstTest();
        LegacyExtension::registerBrowser($this->createMock(Browser::class));

        $extension->executeBeforeTest('test_name');

        $this->assertTrue(BrowserRegistry::default()->isEmpty());
    }

    /**
     * @test
     */
    public function execute_after_test_clears_registry(): void
    {
        $extension = new LegacyExtension();
        $extension->executeBeforeFirstTest();
        $extension->executeBeforeTest('test_name');

        $browser = $this->createMock(Browser::class);
        $browser->method('savedArtifacts')->willReturn([]);
        LegacyExtension::registerBrowser($browser);

        $this->assertFalse(BrowserRegistry::default()->isEmpty());

        $extension->executeAfterTest('test_name', 0.0);

        $this->assertTrue(BrowserRegistry::default()->isEmpty());
    }

    /**
     * @test
     */
    public function execute_after_test_collects_saved_artifacts(): void
    {
        $extension = new LegacyExtension();
        $extension->executeBeforeFirstTest();
        $extension->executeBeforeTest('test_name');

        $browser = $this->createMock(Browser::class);
        $browser->method('savedArtifacts')->willReturn([]);
        LegacyExtension::registerBrowser($browser);

        // Should not throw
        $extension->executeAfterTest('test_name', 0.0);

        $this->addToAssertionCount(1);
    }

    /**
     * @test
     */
    public function execute_after_test_error_triggers_save_current_state(): void
    {
        $extension = new LegacyExtension();
        $extension->executeBeforeFirstTest();
        $extension->executeBeforeTest('test_name');

        $browser = $this->createMock(Browser::class);
        $browser->expects($this->once())
            ->method('saveCurrentState')
        ;
        LegacyExtension::registerBrowser($browser);

        $extension->executeAfterTestError('test_name', 'message', 0.0);
    }

    /**
     * @test
     */
    public function execute_after_test_error_passes_error_filename(): void
    {
        $extension = new LegacyExtension();
        $extension->executeBeforeFirstTest();
        $extension->executeBeforeTest('test_name');

        $browser = $this->createMock(Browser::class);
        $browser->expects($this->once())
            ->method('saveCurrentState')
            ->with($this->stringStartsWith('error_'))
        ;
        LegacyExtension::registerBrowser($browser);

        $extension->executeAfterTestError('test_name', 'message', 0.0);
    }

    /**
     * @test
     */
    public function execute_after_test_error_normalizes_test_name(): void
    {
        $extension = new LegacyExtension();
        $extension->executeBeforeFirstTest();
        $extension->executeBeforeTest('test_name');

        $browser = $this->createMock(Browser::class);
        $browser->expects($this->once())
            ->method('saveCurrentState')
            ->with('error_MyClass__test_method__0')
        ;
        LegacyExtension::registerBrowser($browser);

        $extension->executeAfterTestError('MyClass::test_method', 'message', 0.0);
    }

    /**
     * @test
     */
    public function execute_after_test_failure_triggers_save_current_state(): void
    {
        $extension = new LegacyExtension();
        $extension->executeBeforeFirstTest();
        $extension->executeBeforeTest('test_name');

        $browser = $this->createMock(Browser::class);
        $browser->expects($this->once())
            ->method('saveCurrentState')
        ;
        LegacyExtension::registerBrowser($browser);

        $extension->executeAfterTestFailure('test_name', 'message', 0.0);
    }

    /**
     * @test
     */
    public function execute_after_test_failure_passes_failure_filename(): void
    {
        $extension = new LegacyExtension();
        $extension->executeBeforeFirstTest();
        $extension->executeBeforeTest('test_name');

        $browser = $this->createMock(Browser::class);
        $browser->expects($this->once())
            ->method('saveCurrentState')
            ->with($this->stringStartsWith('failure_'))
        ;
        LegacyExtension::registerBrowser($browser);

        $extension->executeAfterTestFailure('test_name', 'message', 0.0);
    }

    /**
     * @test
     */
    public function execute_after_test_failure_normalizes_test_name(): void
    {
        $extension = new LegacyExtension();
        $extension->executeBeforeFirstTest();
        $extension->executeBeforeTest('test_name');

        $browser = $this->createMock(Browser::class);
        $browser->expects($this->once())
            ->method('saveCurrentState')
            ->with('failure_MyClass__test_method__0')
        ;
        LegacyExtension::registerBrowser($browser);

        $extension->executeAfterTestFailure('MyClass::test_method', 'message', 0.0);
    }

    /**
     * @test
     */
    public function execute_after_last_test_stops_registry(): void
    {
        $extension = new LegacyExtension();
        $extension->executeBeforeFirstTest();

        $this->assertTrue(BrowserRegistry::default()->isStarted());

        $extension->executeAfterLastTest();

        $this->assertFalse(BrowserRegistry::default()->isStarted());
    }

    /**
     * @test
     */
    public function register_browser_adds_to_default_registry(): void
    {
        $extension = new LegacyExtension();
        $extension->executeBeforeFirstTest();

        $browser = $this->createMock(Browser::class);
        LegacyExtension::registerBrowser($browser);

        $this->assertSame([$browser], BrowserRegistry::default()->all());
    }

    /**
     * @test
     */
    public function does_not_save_state_when_registry_is_empty_on_error(): void
    {
        $extension = new LegacyExtension();
        $extension->executeBeforeFirstTest();

        // No browser registered, should not throw
        $extension->executeAfterTestError('test_name', 'message', 0.0);

        $this->addToAssertionCount(1);
    }

    /**
     * @test
     */
    public function does_not_save_state_when_registry_is_empty_on_failure(): void
    {
        $extension = new LegacyExtension();
        $extension->executeBeforeFirstTest();

        // No browser registered, should not throw
        $extension->executeAfterTestFailure('test_name', 'message', 0.0);

        $this->addToAssertionCount(1);
    }

    /**
     * @test
     */
    public function does_not_throw_when_no_browsers_on_finish(): void
    {
        $extension = new LegacyExtension();
        $extension->executeBeforeFirstTest();
        $extension->executeBeforeTest('test_name');

        // No browser registered, should not throw
        $extension->executeAfterTest('test_name', 0.0);

        $this->addToAssertionCount(1);
    }

    /**
     * @test
     */
    public function full_lifecycle_does_not_throw(): void
    {
        $extension = new LegacyExtension();

        $browser = $this->createMock(Browser::class);
        $browser->method('savedArtifacts')->willReturn([]);

        $extension->executeBeforeFirstTest();
        $extension->executeBeforeTest('test_name');
        LegacyExtension::registerBrowser($browser);
        $extension->executeAfterTest('test_name', 0.0);
        $extension->executeAfterLastTest();

        $this->addToAssertionCount(1);
    }
}

<?php

namespace Zenstruck\Browser\Tests;

use Symfony\Component\VarDumper\VarDumper;
use Zenstruck\Browser;
use Zenstruck\Browser\Tests\Extension\HtmlTests;
use Zenstruck\Browser\Tests\Fixture\TestComponent1;
use Zenstruck\Browser\Tests\Fixture\TestComponent2;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait BrowserTests
{
    use HtmlTests;

    /**
     * @test
     */
    public function multiple_browsers(): void
    {
        $browser1 = $this->browser()
            ->visit('/page1')
            ->assertOn('/page1')
        ;

        $browser2 = $this->browser()
            ->visit('/page2')
            ->assertOn('/page2')
        ;

        // this ensures a different browser is actually used
        $browser1->assertOn('/page1');
    }

    /**
     * @test
     */
    public function assert_on(): void
    {
        $this->browser()
            ->visit('/page1')
            ->assertOn('/page1')
            ->assertOn('http://www.example.com/page1')
            ->assertNotOn('/page2')
            ->assertNotOn('http://www.example.com/page1', ['path', 'host'])
            ->visit('/page1?foo=bar')
            ->assertOn('/page1?foo=bar')
            ->assertOn('/page1', ['path'])
            ->assertOn('/page1', ['path', 'fragment'])
            ->assertNotOn('/page1?foo=baz')
        ;
    }

    /**
     * @test
     */
    public function can_use_current_browser(): void
    {
        $browser = $this->browser();

        $browser
            ->use(function(Browser $b) use ($browser) {
                $this->assertSame($b, $browser);

                $browser->visit('/redirect1');
            })
            ->assertOn('/page1')
            ->use(function() {
                $this->assertTrue(true);
            })
        ;
    }

    /**
     * @test
     */
    public function can_use_components(): void
    {
        $this->browser()
            ->use(function(TestComponent1 $component) {
                $component->assertTitle('h1 title');
            })
            ->assertOn('/page1')
        ;
    }

    /**
     * @test
     */
    public function component_pre_assertions_and_actions_are_called(): void
    {
        $this->browser()
            ->use(function(TestComponent2 $component) {
                $this->assertTrue($component->preActionsCalled);
                $this->assertTrue($component->preAssertionsCalled);
            })
        ;
    }

    /**
     * @test
     */
    public function with_can_accept_multiple_browsers_and_components(): void
    {
        $this->browser()
            ->use(function(Browser $browser1, $browser2, TestComponent1 $component1, TestComponent2 $component2) {
                $this->assertInstanceOf(Browser::class, $browser1);
                $this->assertInstanceOf(Browser::class, $browser2);
                $this->assertInstanceOf($this->browserClass(), $browser1);
                $this->assertInstanceOf($this->browserClass(), $browser2);
                $this->assertInstanceOf(TestComponent1::class, $component1);
                $this->assertInstanceOf(TestComponent2::class, $component2);
            })
        ;
    }

    /**
     * @test
     */
    public function invalid_with_callback_parameter_throws_type_error(): void
    {
        $this->expectException(\TypeError::class);

        $this->browser()->use(function(string $invalidType) {});
    }

    /**
     * @test
     */
    public function redirects_are_followed_by_default(): void
    {
        $this->browser()
            ->visit('/redirect1')
            ->assertOn('/page1')
        ;
    }

    /**
     * @test
     */
    public function content_assertions(): void
    {
        $this->browser()
            ->visit('/page1')
            ->assertContains('h1 title')
            ->assertNotContains('invalid text')
        ;
    }

    /**
     * @test
     */
    public function can_dump_response(): void
    {
        $dumpedValues[] = null;

        VarDumper::setHandler(function($var) use (&$dumpedValues) {
            $dumpedValues[] = $var;
        });

        $this->browser()
            ->visit('/page1')
            ->dump()
        ;

        VarDumper::setHandler();

        // a null value is added to the beginning
        $dumped = \array_values(\array_filter($dumpedValues))[0];

        $this->assertStringContainsString('/page1', $dumped);
        $this->assertStringContainsString('<html', $dumped);
        $this->assertStringContainsString('<h1>h1 title</h1>', $dumped);
    }

    /**
     * @test
     */
    public function can_save_source(): void
    {
        $file = __DIR__.'/../var/browser/source/source.txt';

        if (\file_exists($file)) {
            \unlink($file);
        }

        $this->browser()
            ->visit('/page1')
            ->saveSource('source.txt')
        ;

        $this->assertFileExists($file);

        $contents = \file_get_contents($file);

        $this->assertStringContainsString('/page1', $contents);
        $this->assertStringContainsString('<html', $contents);
        $this->assertStringContainsString('<h1>h1 title</h1>', $contents);

        \unlink($file);
    }

    abstract protected static function browserClass(): string;
}

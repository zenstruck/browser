<?php

namespace Zenstruck\Browser\Tests;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\VarDumper\VarDumper;
use Zenstruck\Browser;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Browser\Tests\Extension\HtmlTests;
use Zenstruck\Browser\Tests\Fixture\TestComponent1;
use Zenstruck\Browser\Tests\Fixture\TestComponent2;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait BrowserTests
{
    use HasBrowser, HtmlTests;

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
     * @dataProvider encodedUrlProvider
     */
    public function assert_on_encoded($url, $expected): void
    {
        $this->browser()
            ->visit($url)
            ->assertOn($expected)
        ;
    }

    public static function encodedUrlProvider(): iterable
    {
        yield ['/page1?filter[q]=value', '/page1?filter[q]=value'];
        yield ['/page1?filter%5Bq%5D=value', '/page1?filter[q]=value'];
        yield ['/page1?filter[q]=value', '/page1?filter%5Bq%5D=value'];
        yield ['/page1#foo bar', '/page1#foo bar'];
        yield ['/page1#foo%20bar', '/page1#foo bar'];
        yield ['/page1#foo bar', '/page1#foo%20bar'];
        yield ['/page1#foo+bar', '/page1#foo bar'];
        yield ['/page1#foo bar', '/page1#foo+bar'];
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
        $browser = $this->browser();

        $browser
            ->use(function(Browser $browser1, $browser2, TestComponent1 $component1, TestComponent2 $component2) use ($browser) {
                $this->assertInstanceOf(Browser::class, $browser1);
                $this->assertInstanceOf(Browser::class, $browser2);
                $this->assertInstanceOf(\get_class($browser), $browser1);
                $this->assertInstanceOf(\get_class($browser), $browser2);
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
        $output = self::catchVarDumperOutput(function() {
            $this->browser()
                ->visit('/page1')
                ->dump()
            ;
        });

        $this->assertStringContainsString('/page1', $output[0]);
        $this->assertStringContainsString('<html', $output[0]);
        $this->assertStringContainsString('<h1>h1 title</h1>', $output[0]);
    }

    /**
     * @test
     */
    public function can_save_source(): void
    {
        $contents = self::catchFileContents(__DIR__.'/../var/browser/source/source.txt', function() {
            $this->browser()
                ->visit('/page1')
                ->saveSource('source.txt')
            ;
        });

        $this->assertStringContainsString('/page1', $contents);
        $this->assertStringContainsString('<html', $contents);
        $this->assertStringContainsString('<h1>h1 title</h1>', $contents);
    }

    protected static function catchFileContents(string $expectedFile, callable $callback): string
    {
        (new Filesystem())->remove($expectedFile);

        $callback();

        self::assertFileExists($expectedFile);

        return \file_get_contents($expectedFile);
    }

    protected static function catchVarDumperOutput(callable $callback): array
    {
        $output[] = null;

        VarDumper::setHandler(function($var) use (&$output) {
            $output[] = $var;
        });

        $callback();

        // reset to default handler
        VarDumper::setHandler();

        // a null value is added to the beginning
        return \array_values(\array_filter($output));
    }

    abstract protected function browser(): Browser;
}

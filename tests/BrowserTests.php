<?php

namespace Zenstruck\Browser\Tests;

use Symfony\Component\VarDumper\VarDumper;
use Zenstruck\Browser;
use Zenstruck\Browser\Tests\Fixture\TestComponent1;
use Zenstruck\Browser\Tests\Fixture\TestComponent2;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @method Browser browser()
 */
trait BrowserTests
{
    /**
     * @test
     */
    public function can_use_with(): void
    {
        $this->browser()
            ->with(function(Browser $browser) {
                $browser->visit('/redirect1');
            })
            ->assertOn('/page1')
            ->with(function() {
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
            ->with(function(TestComponent1 $component) {
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
            ->with(function(TestComponent2 $component) {
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
            ->with(function(Browser $browser1, $browser2, TestComponent1 $component1, TestComponent2 $component2) {
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

        $this->browser()->with(function(string $invalidType) {});
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
    public function exceptions_are_caught_by_default(): void
    {
        $this->browser()
            ->visit('/exception')
            ->assertStatus(500)
        ;
    }

    /**
     * @test
     */
    public function response_assertions(): void
    {
        $this->browser()
            ->visit('/page1')
            ->assertResponseContains('h1 title')
            ->assertResponseNotContains('invalid text')
        ;
    }

    /**
     * @test
     */
    public function response_header_assertions(): void
    {
        $this->browser()
            ->visit('/page1')
            ->assertHeaderEquals('Content-Type', 'text/html; charset=UTF-8')
            ->assertHeaderContains('Content-Type', 'text/html')
        ;
    }

    /**
     * @test
     */
    public function html_assertions(): void
    {
        $this->browser()
            ->visit('/page1')
            ->assertSee('h1 title')
            ->assertNotSee('invalid text')
            ->assertSeeIn('h1', 'title')
            ->assertNotSeeIn('h1', 'invalid text')
            ->assertSeeElement('h1')
            ->assertNotSeeElement('h2')
            ->assertElementCount('ul li', 2)
        ;
    }

    /**
     * @test
     */
    public function html_head_assertions(): void
    {
        $this->browser()
            ->visit('/page1')
            ->assertSeeIn('title', 'meta title')
            ->assertElementAttributeContains('meta[name="description"]', 'content', 'meta')
            ->assertElementAttributeNotContains('meta[name="description"]', 'content', 'invalid')
        ;
    }

    /**
     * @test
     */
    public function form_assertions(): void
    {
        $this->browser()
            ->visit('/page1')
            ->assertFieldEquals('Input 1', 'input 1')
            ->assertFieldEquals('input1', 'input 1')
            ->assertFieldEquals('input_1', 'input 1')
            ->assertChecked('Input 3')
            ->assertChecked('input3')
            ->assertChecked('input_3')
            ->assertNotChecked('Input 2')
            ->assertNotChecked('input2')
            ->assertNotChecked('input_2')
        ;
    }

    /**
     * @test
     */
    public function link_action(): void
    {
        $this->browser()
            ->visit('/page1')
            ->follow('a link')
            ->assertOn('/page2')
        ;
    }

    /**
     * @test
     */
    public function form_actions(): void
    {
        $this->browser()
            ->visit('/page1')
            ->fillField('input1', 'Kevin')
            ->checkField('input2')
            ->uncheckField('input3')
            ->selectFieldOption('input4', 'option 2')
            ->attachFile('input5', __FILE__)
            ->selectFieldOptions('input6', ['option 1', 'option 3'])
            ->press('Submit')
            ->assertOn('/submit-form')
            ->assertResponseContains('"input_1":"Kevin"')
            ->assertResponseContains('"input_2":"on"')
            ->assertResponseNotContains('"input_3')
            ->assertResponseContains('"input_4":"option 2"')
            ->assertResponseContains(\sprintf('"input_5":"%s"', \pathinfo(__FILE__, PATHINFO_BASENAME)))
            ->assertResponseContains('"input_6":["option 1","option 3"]')
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
        $dumpedValues = \array_values(\array_filter($dumpedValues));

        $this->assertCount(3, $dumpedValues);
        $this->assertStringContainsString('/page1', $dumpedValues[0]);
        $this->assertStringContainsString('<h1>h1 title</h1>', $dumpedValues[1]);
        $this->assertStringContainsString('/page1', $dumpedValues[2]);
    }

    /**
     * @test
     */
    public function can_dump_html_element(): void
    {
        $dumpedValues[] = null;

        VarDumper::setHandler(function($var) use (&$dumpedValues) {
            $dumpedValues[] = $var;
        });

        $this->browser()
            ->visit('/page1')
            ->dump('p#link')
        ;

        VarDumper::setHandler();

        // a null value is added to the beginning
        $dumpedValues = \array_values(\array_filter($dumpedValues));

        $this->assertCount(3, $dumpedValues);
        $this->assertStringContainsString('/page1', $dumpedValues[0]);
        $this->assertSame('<a href="/page2">a link</a> not a link', $dumpedValues[1]);
        $this->assertStringContainsString('/page1', $dumpedValues[2]);
    }

    /**
     * @test
     */
    public function can_dump_json_response_as_array(): void
    {
        $dumpedValues[] = null;

        VarDumper::setHandler(function($var) use (&$dumpedValues) {
            $dumpedValues[] = $var;
        });

        $this->browser()
            ->post('/json', ['json' => $expected = ['foo' => 'bar']])
            ->dump()
        ;

        VarDumper::setHandler();

        // a null value is added to the beginning
        $dumpedValues = \array_values(\array_filter($dumpedValues));

        $this->assertSame($expected, $dumpedValues[1]);
    }

    /**
     * @test
     */
    public function can_dump_json_array_key(): void
    {
        $dumpedValues[] = null;

        VarDumper::setHandler(function($var) use (&$dumpedValues) {
            $dumpedValues[] = $var;
        });

        $this->browser()
            ->post('/json', ['json' => ['foo' => 'bar']])
            ->dump('foo')
        ;

        VarDumper::setHandler();

        // a null value is added to the beginning
        $dumpedValues = \array_values(\array_filter($dumpedValues));

        $this->assertSame('bar', $dumpedValues[1]);
    }

    /**
     * @test
     */
    public function can_dump_json_path_expression(): void
    {
        $dumpedValues[] = null;

        VarDumper::setHandler(function($var) use (&$dumpedValues) {
            $dumpedValues[] = $var;
        });

        $this->browser()
            ->post('/json', ['json' => [
                'foo' => [
                    'bar' => ['baz' => 1],
                    'bam' => ['baz' => 2],
                    'boo' => ['baz' => 3],
                ],
            ]])
            ->dump('foo.*.baz')
        ;

        VarDumper::setHandler();

        // a null value is added to the beginning
        $dumpedValues = \array_values(\array_filter($dumpedValues));

        $this->assertSame([1, 2, 3], $dumpedValues[1]);
    }

    abstract protected static function browserClass(): string;
}

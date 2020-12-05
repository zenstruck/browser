<?php

namespace Zenstruck\Browser\Tests;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Zenstruck\Browser;
use Zenstruck\Browser\HttpOptions;
use Zenstruck\Browser\Tests\Fixture\CustomHttpOptions;
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
    public function the_container_is_injected(): void
    {
        $this->assertInstanceOf(ContainerInterface::class, $this->browser()->container());
    }

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
    public function can_intercept_redirects(): void
    {
        $this->browser()
            ->interceptRedirects()
            ->visit('/redirect1')
            ->assertOn('/redirect1')
            ->assertRedirected()
            ->followRedirect()
            ->assertOn('/redirect2')
            ->assertRedirected()
            ->followRedirect()
            ->assertOn('/redirect3')
            ->assertRedirected()
            ->followRedirect()
            ->assertOn('/page1')
            ->assertSuccessful()
        ;
    }

    /**
     * @test
     */
    public function can_assert_redirected_to(): void
    {
        $this->browser()
            ->interceptRedirects()
            ->visit('/redirect3')
            ->assertRedirectedTo('/page1')
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
    public function http_method_actions(): void
    {
        $this->browser()
            ->get('/http-method')
            ->assertSuccessful()
            ->assertResponseContains('"method":"GET"')
            ->post('/http-method')
            ->assertSuccessful()
            ->assertResponseContains('"method":"POST"')
            ->delete('/http-method')
            ->assertSuccessful()
            ->assertResponseContains('"method":"DELETE"')
            ->put('/http-method')
            ->assertSuccessful()
            ->assertResponseContains('"method":"PUT"')
            ->assertResponseContains('"ajax":false')
            ->post('/http-method', [
                'json' => ['foo' => 'bar'],
                'headers' => ['X-Foo' => 'Bar'],
                'ajax' => true,
            ])
            ->assertResponseContains('"content-type":["application\/json"]')
            ->assertResponseContains('"x-foo":["Bar"]')
            ->assertResponseContains('"content":"{\u0022foo\u0022:\u0022bar\u0022}"')
            ->assertResponseContains('"ajax":true')
            ->post('/http-method', HttpOptions::jsonAjax(['foo' => 'bar'])->withHeader('X-Foo', 'Bar'))
            ->assertResponseContains('"content-type":["application\/json"]')
            ->assertResponseContains('"x-foo":["Bar"]')
            ->assertResponseContains('"content":"{\u0022foo\u0022:\u0022bar\u0022}"')
            ->assertResponseContains('"ajax":true')
            ->post('/http-method', CustomHttpOptions::api('my-token'))
            ->assertResponseContains('"content-type":["application\/json"]')
            ->assertResponseContains('"x-token":["my-token"]')
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
            ->press('Submit')
            ->assertOn('/submit-form')
            ->assertResponseContains('"input_1":"Kevin"')
            ->assertResponseContains('"input_2":"on"')
            ->assertResponseNotContains('"input_3')
            ->assertResponseContains('"input_4":"option 2"')
            ->assertResponseContains(\sprintf('"input_5":"%s"', \pathinfo(__FILE__, PATHINFO_BASENAME)))
        ;
    }

    /**
     * @test
     */
    public function form_multiselect(): void
    {
        $this->browser()
            ->visit('/page1')
            ->selectFieldOptions('input6', ['option 1', 'option 3'])
            ->press('Submit')
            ->assertOn('/submit-form')
            ->assertResponseContains('"input_6":["option 1","option 3"]')
        ;
    }

    abstract protected static function browserClass(): string;
}

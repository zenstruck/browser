<?php

namespace Zenstruck\Browser\Tests\Extension;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait HtmlTests
{
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
            ->assertElementAttributeContains('html', 'lang', 'en')
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
            ->assertFieldNotEquals('Input 1', 'invalid')
            ->assertFieldNotEquals('input1', 'invalid')
            ->assertFieldNotEquals('input_1', 'invalid')
            ->assertChecked('Input 3')
            ->assertChecked('input3')
            ->assertChecked('input_3')
            ->assertNotChecked('Input 2')
            ->assertNotChecked('input2')
            ->assertNotChecked('input_2')
            ->assertSelected('Input 4', 'option 1')
            ->assertSelected('input4', 'option 1')
            ->assertSelected('input_4', 'option 1')
            ->assertSelected('Input 7', 'option 1')
            ->assertSelected('input7', 'option 1')
            ->assertSelected('input_7[]', 'option 1')
            ->assertSelected('Input 7', 'option 3')
            ->assertSelected('input7', 'option 3')
            ->assertSelected('input_7[]', 'option 3')
            ->assertNotSelected('Input 4', 'option 2')
            ->assertNotSelected('input4', 'option 2')
            ->assertNotSelected('input_4', 'option 2')
            ->assertNotSelected('Input 7', 'option 2')
            ->assertNotSelected('input7', 'option 2')
            ->assertNotSelected('input_7[]', 'option 2')
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
            ->visit('/page1')
            ->click('a link')
            ->assertOn('/page2')
        ;
    }

    /**
     * @test
     */
    public function form_actions_by_field_label(): void
    {
        $this->browser()
            ->visit('/page1')
            ->fillField('Input 1', 'Kevin')
            ->checkField('Input 2')
            ->uncheckField('Input 3')
            ->selectFieldOption('Input 4', 'option 2')
            ->attachFile('Input 5', __FILE__)
            ->selectFieldOptions('Input 6', ['option 1', 'option 3'])
            ->click('Submit')
            ->assertOn('/submit-form')
            ->assertContains('"input_1":"Kevin"')
            ->assertContains('"input_2":"on"')
            ->assertNotContains('"input_3')
            ->assertContains('"input_4":"option 2"')
            ->assertContains(\sprintf('"input_5":"%s"', \pathinfo(__FILE__, PATHINFO_BASENAME)))
            ->assertContains('"input_6":["option 1","option 3"]')
        ;
    }

    /**
     * @test
     */
    public function form_actions_by_field_id(): void
    {
        $this->browser()
            ->visit('/page1')
            ->fillField('input1', 'Kevin')
            ->checkField('input2')
            ->uncheckField('input3')
            ->selectFieldOption('input4', 'option 2')
            ->attachFile('input5', __FILE__)
            ->selectFieldOptions('input6', ['option 1', 'option 3'])
            ->click('Submit')
            ->assertOn('/submit-form')
            ->assertContains('"input_1":"Kevin"')
            ->assertContains('"input_2":"on"')
            ->assertNotContains('"input_3')
            ->assertContains('"input_4":"option 2"')
            ->assertContains(\sprintf('"input_5":"%s"', \pathinfo(__FILE__, PATHINFO_BASENAME)))
            ->assertContains('"input_6":["option 1","option 3"]')
        ;
    }

    /**
     * @test
     */
    public function form_actions_by_field_name(): void
    {
        $this->browser()
            ->visit('/page1')
            ->fillField('input_1', 'Kevin')
            ->checkField('input_2')
            ->uncheckField('input_3')
            ->selectFieldOption('input_4', 'option 2')
            ->attachFile('input_5', __FILE__)
            ->selectFieldOptions('input_6[]', ['option 1', 'option 3'])
            ->click('Submit')
            ->assertOn('/submit-form')
            ->assertContains('"input_1":"Kevin"')
            ->assertContains('"input_2":"on"')
            ->assertNotContains('"input_3')
            ->assertContains('"input_4":"option 2"')
            ->assertContains(\sprintf('"input_5":"%s"', \pathinfo(__FILE__, PATHINFO_BASENAME)))
            ->assertContains('"input_6":["option 1","option 3"]')
        ;
    }

    /**
     * @test
     */
    public function can_dump_html_element(): void
    {
        $output = self::catchVarDumperOutput(function() {
            $this->browser()
                ->visit('/page1')
                ->dump('p#link')
            ;
        });

        $this->assertCount(1, $output);
        $this->assertSame('<a href="/page2">a link</a> not a link', $output[0]);
    }

    /**
     * @test
     */
    public function if_dump_selector_matches_multiple_elements_all_are_dumped(): void
    {
        $output = self::catchVarDumperOutput(function() {
            $this->browser()
                ->visit('/page1')
                ->dump('li')
            ;
        });

        $this->assertCount(2, $output);
        $this->assertSame('list 1', $output[0]);
        $this->assertSame('list 2', $output[1]);
    }
}

<?php

namespace Zenstruck\Browser\Tests;

use Symfony\Component\Panther\PantherTestCase;
use Zenstruck\Browser\PantherBrowser;
use Zenstruck\Browser\Test\HasPantherBrowser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @group panther
 */
final class PantherBrowserTest extends PantherTestCase
{
    use BrowserTests, HasPantherBrowser;

    /**
     * @test
     */
    public function can_take_screenshot(): void
    {
        $file = \sys_get_temp_dir().'/zenstruck-browser/screen.png';

        if (\file_exists($file)) {
            \unlink($file);
        }

        $this->browser()
            ->visit('/page1')
            ->takeScreenshot($file)
        ;

        $this->assertFileExists($file);

        \unlink($file);
    }

    /**
     * @test
     */
    public function can_wait(): void
    {
        $this->browser()
            ->visit('/javascript')
            ->assertNotSeeIn('#timeout-box', 'Contents of timeout box')
            ->wait(600)
            ->assertSeeIn('#timeout-box', 'Contents of timeout box')
        ;
    }

    /**
     * @test
     */
    public function can_wait_until_visible_and_not_visible(): void
    {
        $this->browser()
            ->visit('/javascript')
            ->assertNotSeeIn('#timeout-box', 'Contents of timeout box')
            ->waitUntilVisible('#timeout-box')
            ->assertSeeIn('#timeout-box', 'Contents of timeout box')
            ->assertNotSeeIn('#show-box', 'Contents of show box')
            ->click('show')
            ->waitUntilVisible('#show-box')
            ->assertSeeIn('#show-box', 'Contents of show box')
            ->assertSeeIn('#hide-box', 'Contents of hide box')
            ->click('hide')
            ->waitUntilNotVisible('#hide-box')
            ->assertNotSeeIn('#hide-box', 'Contents of hide box')
            ->assertSeeIn('#toggle-box', 'Contents of toggle box')
            ->click('toggle')
            ->waitUntilNotVisible('#toggle-box')
            ->assertNotSeeIn('#toggle-box', 'Contents of toggle box')
            ->click('toggle')
            ->waitUntilVisible('#toggle-box')
            ->assertSeeIn('#toggle-box', 'Contents of toggle box')
        ;
    }

    /**
     * @test
     */
    public function can_wait_until_see_in_and_not_see_in(): void
    {
        $this->browser()
            ->visit('/javascript')
            ->assertNotSeeIn('#timeout-box', 'Contents of timeout box')
            ->waitUntilSeeIn('#timeout-box', 'timeout')
            ->assertSeeIn('#timeout-box', 'Contents of timeout box')
            ->assertNotSeeIn('#show-box', 'Contents of show box')
            ->click('show')
            ->waitUntilSeeIn('#show-box', 'show')
            ->assertSeeIn('#show-box', 'Contents of show box')
            ->assertSeeIn('#hide-box', 'Contents of hide box')
            ->click('hide')
            ->waitUntilNotSeeIn('#hide-box', 'hide')
            ->assertNotSeeIn('#hide-box', 'Contents of hide box')
            ->assertNotSeeIn('#output', 'some text')
            ->fillField('input', 'some text')
            ->click('submit')
            ->waitUntilSeeIn('#output', 'some text')
            ->assertSeeIn('#output', 'some text')
            ->click('clear')
            ->waitUntilNotSeeIn('#output', 'some text')
            ->assertNotSeeIn('#output', 'some text')
        ;
    }

    /**
     * @test
     */
    public function can_check_if_element_is_visible_and_not_visible(): void
    {
        $this->browser()
            ->visit('/javascript')
            ->assertVisible('#hide-box')
            ->assertNotVisible('#show-box')
            ->assertNotVisible('#invalid-element')
        ;
    }

    /**
     * @test
     */
    public function exceptions_are_caught_by_default(): void
    {
        $this->markTestSkipped('Panther does not support response status codes.');
    }

    /**
     * @test
     */
    public function response_header_assertions(): void
    {
        $this->markTestSkipped('Panther cannot access the response headers.');
    }

    /**
     * TODO - this should be possible as Panther has access to the page source.
     *
     * @test
     */
    public function html_head_assertions(): void
    {
        $this->markTestIncomplete('Panther cannot access <head>.');
    }

    /**
     * @test
     */
    public function can_dump_json_response_as_array(): void
    {
        $this->markTestSkipped('Panther does not support json responses.');
    }

    /**
     * @test
     */
    public function can_dump_json_array_key(): void
    {
        $this->markTestSkipped('Panther does not support json responses.');
    }

    /**
     * @test
     */
    public function can_dump_json_path_expression(): void
    {
        $this->markTestSkipped('Panther does not support json responses.');
    }

    /**
     * @test
     */
    public function can_save_formatted_json_source(): void
    {
        $this->markTestSkipped('Panther does not support json responses.');
    }

    protected static function browserClass(): string
    {
        return PantherBrowser::class;
    }
}

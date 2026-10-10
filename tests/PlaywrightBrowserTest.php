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

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Browser\Modifier;
use Zenstruck\Browser\PlaywrightBrowser;
use Zenstruck\Browser\Test\ArtifactTracker;
use Zenstruck\Browser\Test\HasBrowser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[Group('playwright')]
class PlaywrightBrowserTest extends KernelTestCase
{
    use BrowserTests, HasBrowser;

    #[Test]
    public function can_use_playwright_browser_as_typehint(): void
    {
        $this->browser()
            ->use(static function(PlaywrightBrowser $browser) {
                $browser->visit('/page1');
            })
            ->assertOn('/page1')
        ;
    }

    #[Test]
    public function extension_saves_a_screenshot_and_console_log_when_a_test_fails(): void
    {
        $extension = new ArtifactTracker();
        $extension->executeBeforeFirstTest();
        $extension->executeBeforeTest('X::y');

        $this->browser()->visit('/javascript');

        $extension->executeAfterTestError('X::y', 'the error message', 0.0);

        $this->assertFileExists(__DIR__.'/../var/browser/source/error_X__y__0.html');
        $this->assertFileExists(__DIR__.'/../var/browser/screenshots/error_X__y__0.png');
        $this->assertFileExists(__DIR__.'/../var/browser/console-logs/error_X__y__0.log');
    }

    #[Test]
    public function can_take_screenshot(): void
    {
        self::catchFileContents(__DIR__.'/../var/browser/screenshots/screen.png', function() {
            $this->browser()
                ->visit('/page1')
                ->takeScreenshot('screen.png')
            ;
        });
    }

    #[Test]
    public function can_wait(): void
    {
        $this->browser()
            ->visit('/javascript')
            ->assertNotSeeIn('#timeout-box', 'Contents of timeout box')
            ->wait(600)
            ->assertSeeIn('#timeout-box', 'Contents of timeout box')
        ;
    }

    #[Test]
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

    #[Test]
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

    #[Test]
    public function can_check_if_element_is_visible_and_not_visible(): void
    {
        $this->browser()
            ->visit('/javascript')
            ->assertVisible('#hide-box')
            ->assertNotVisible('#show-box')
            ->assertNotVisible('#invalid-element')
        ;
    }

    #[Test]
    public function stylesheet_hidden_elements_are_not_visible(): void
    {
        // a class, not an inline style: only the browser can resolve this
        $this->browser()
            ->visit('/javascript')
            ->assertNotVisible('#stylesheet-box')
            ->assertNotSeeIn('#stylesheet-box', 'Contents of stylesheet box')
        ;
    }

    #[Test]
    public function can_save_console_log(): void
    {
        $contents = self::catchFileContents(__DIR__.'/../var/browser/console-logs/console.log', function() {
            $this->browser()
                ->visit('/javascript')
                ->click('log error')
                ->saveConsoleLog('console.log')
            ;
        });

        $this->assertStringContainsString('"type": "error"', $contents);
        $this->assertStringContainsString('console.error message', $contents);
    }

    #[Test]
    public function can_dump_console_log_with_console_error(): void
    {
        $output = self::catchVarDumperOutput(function() {
            $this->browser()
                ->visit('/javascript')
                ->click('log error')
                ->dumpConsoleLog()
            ;
        });

        $this->assertStringContainsString('console.error message', \json_encode($output, \JSON_THROW_ON_ERROR));
    }

    #[Test]
    public function can_dump_console_log_with_throw_error(): void
    {
        $this->markTestSkipped('Uncaught JS errors are a Playwright "pageerror" event, which playwright-php does not expose.');

        $output = self::catchVarDumperOutput(function() {
            $this->browser()
                ->visit('/javascript')
                ->click('throw error')
                ->dumpConsoleLog()
            ;
        });

        $this->assertStringContainsString('Error: error object message', \json_encode($output, \JSON_THROW_ON_ERROR));
    }

    #[Test]
    public function cannot_follow_invisible_link(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Clickable element "invisible link" is not visible.');

        $this->browser()
            ->visit('/javascript')
            ->click('invisible link')
        ;
    }

    #[Test]
    public function can_double_click_an_element(): void
    {
        $this->browser()
            ->visit('/page1')
            ->click('#double-click-link a')
            ->assertOn('/page1')
        ;

        $this->browser()
            ->visit('/page1')
            ->doubleClick('#double-click-link a')
            ->assertOn('/page2')
            ->assertSee('success')
        ;
    }

    #[Test]
    public function context_menu_on_element(): void
    {
        $this->browser()
            ->visit('/page1')
            ->click('#double-click-link a')
            ->assertOn('/page1')
        ;

        $this->browser()
            ->visit('/page1')
            ->rightClick('#context-menu-link a')
            ->assertOn('/page2')
            ->assertSee('success')
        ;
    }

    #[Test]
    public function click_with_modifiers(): void
    {
        $this->browser()
            ->visit('/page1')
            ->click('#shift-click-link a')
            ->assertOn('/page1')
            ->click('#shift-click-link a', Modifier::Shift)
            ->assertOn('/page2')
            ->visit('/page1')
            ->click('#shift-click-link a', 'shift')
            ->assertOn('/page2')
        ;
    }

    #[Test]
    public function can_hover_an_element(): void
    {
        $this->browser()
            ->visit('/javascript')
            ->assertNotVisible('#hover-menu')
            ->hover('#hover-target')
            ->assertVisible('#hover-menu')
        ;
    }

    #[Test]
    public function can_hover_with_modifiers(): void
    {
        $this->browser()
            ->visit('/javascript')
            ->hover('#alt-hover-target')
            ->assertNotSeeIn('#alt-hover-output', 'alt hovered')
            ->hover('#hover-target')
            ->hover('#alt-hover-target', 'alt')
            ->assertSeeIn('#alt-hover-output', 'alt hovered')
        ;
    }

    #[Test]
    public function can_focus_and_blur_an_element(): void
    {
        $this->browser()
            ->visit('/javascript')
            ->assertNotSeeIn('#focus-output', 'focused')
            ->focus('#focus-input')
            ->assertSeeIn('#focus-output', 'focused')
            ->blur('#focus-input')
            ->assertSeeIn('#focus-output', 'blurred')
        ;
    }

    #[Test]
    public function can_press_a_key_combination(): void
    {
        $this->browser()
            ->visit('/javascript')
            ->press('/')
            ->assertNotSeeIn('#keyboard-output', 'search opened')
            ->press('ControlOrMeta+/')
            ->assertSeeIn('#keyboard-output', 'search opened')
        ;
    }

    #[Test]
    public function can_press_a_key_combination_built_from_a_modifier(): void
    {
        $this->browser()
            ->visit('/javascript')
            ->press(Modifier::ControlOrMeta->plus('/'))
            ->assertSeeIn('#keyboard-output', 'search opened')
        ;
    }

    #[Test]
    public function can_press_a_key_sequence(): void
    {
        $this->browser()
            ->visit('/javascript')
            ->press('Shift')
            ->assertNotSeeIn('#keyboard-output', 'double shift')
            ->visit('/javascript')
            ->press('Shift', 'Shift')
            ->assertSeeIn('#keyboard-output', 'double shift')
        ;
    }

    #[Test]
    public function can_press_a_key_on_the_focused_element(): void
    {
        $this->browser()
            ->visit('/javascript')
            ->focus('#input')
            ->press('a', 'b')
            ->click('#submit')
            ->assertSeeIn('#output', 'ab')
        ;
    }

    #[Test]
    public function pressing_enter_submits_a_form(): void
    {
        $this->browser()
            ->visit('/page1')
            ->fillField('input1', 'pressed')
            ->press('Enter')
            ->assertOn('/submit-form')
            ->assertContains('"input_1":"pressed"')
        ;
    }

    protected function browser(): PlaywrightBrowser
    {
        return $this->playwrightBrowser();
    }
}

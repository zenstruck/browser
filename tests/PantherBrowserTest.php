<?php

namespace Zenstruck\Browser\Tests;

use PHPUnit\Framework\AssertionFailedError;
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
    public function can_use_panther_browser_as_typehint(): void
    {
        $this->browser()
            ->use(function(PantherBrowser $browser) {
                $browser->visit('/page1');
            })
            ->assertOn('/page1')
        ;
    }

    /**
     * @test
     */
    public function can_take_screenshot(): void
    {
        self::catchFileContents(__DIR__.'/../var/browser/screenshots/screen.png', function() {
            $this->browser()
                ->visit('/page1')
                ->takeScreenshot('screen.png')
            ;
        });
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
    public function can_save_console_log(): void
    {
        $contents = self::catchFileContents(__DIR__.'/../var/browser/console-logs/console.log', function() {
            $this->browser()
                ->visit('/javascript')
                ->click('log error')
                ->saveConsoleLog('console.log')
            ;
        });

        $this->assertStringContainsString('        "level": "SEVERE",', $contents);
        $this->assertStringContainsString('console.error message', $contents);
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function can_dump_console_log_with_throw_error(): void
    {
        $output = self::catchVarDumperOutput(function() {
            $this->browser()
                ->visit('/javascript')
                ->click('throw error')
                ->dumpConsoleLog()
            ;
        });

        $this->assertStringContainsString('Error: error object message', \json_encode($output, \JSON_THROW_ON_ERROR));
    }

    /**
     * @test
     */
    public function cannot_follow_invisible_link(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectErrorMessage('Link "invisible link" is not visible.');

        $this->browser()
            ->visit('/javascript')
            ->follow('invisible link')
        ;
    }

    protected function browser(): PantherBrowser
    {
        return $this->pantherBrowser();
    }
}

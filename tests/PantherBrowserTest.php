<?php

namespace Zenstruck\Browser\Tests;

use Symfony\Component\Panther\PantherTestCase;
use Symfony\Component\VarDumper\VarDumper;
use Zenstruck\Browser\PantherBrowser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @group panther
 */
final class PantherBrowserTest extends PantherTestCase
{
    use BrowserTests;

    /**
     * @test
     */
    public function can_take_screenshot(): void
    {
        $file = __DIR__.'/../var/browser/screenshots/screen.png';

        if (\file_exists($file)) {
            \unlink($file);
        }

        $this->browser()
            ->visit('/page1')
            ->takeScreenshot('screen.png')
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
    public function can_save_console_log(): void
    {
        $file = __DIR__.'/../var/browser/console-logs/console.log';

        if (\file_exists($file)) {
            \unlink($file);
        }

        $this->browser()
            ->visit('/javascript')
            ->click('log')
            ->saveConsoleLog('console.log')
        ;

        $this->assertFileExists($file);

        $contents = \file_get_contents($file);

        $this->assertStringContainsString('        "level": "SEVERE",', $contents);
        $this->assertStringContainsString('error!', $contents);

        \unlink($file);
    }

    /**
     * @test
     */
    public function can_dump_console_log(): void
    {
        $dumpedValues[] = null;

        VarDumper::setHandler(function($var) use (&$dumpedValues) {
            $dumpedValues[] = $var;
        });

        $this->browser()
            ->visit('/javascript')
            ->click('log')
            ->dumpConsoleLog()
        ;

        VarDumper::setHandler();

        // a null value is added to the beginning
        $dumpedValues = \array_values(\array_filter($dumpedValues));

        $this->assertSame('SEVERE', $dumpedValues[0][0]['level']);
        $this->assertStringContainsString('error!', $dumpedValues[0][0]['message']);
    }

    protected function browser(): PantherBrowser
    {
        return $this->pantherBrowser();
    }
}

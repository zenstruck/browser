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

    protected static function browserClass(): string
    {
        return PantherBrowser::class;
    }
}

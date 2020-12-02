<?php

namespace Zenstruck\Browser\Tests;

use Symfony\Component\Panther\PantherTestCase;
use Zenstruck\Browser\Test\HasPantherBrowser;

/**
 * @group panther
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PantherBrowserTest extends PantherTestCase
{
    use HasPantherBrowser, BrowserTests;

    /**
     * @test
     */
    public function can_intercept_redirects(): void
    {
        $this->markTestSkipped('Redirects cannot be intercepted with panther.');
    }

    /**
     * @test
     */
    public function can_assert_redirected_to(): void
    {
        $this->markTestSkipped('Redirects cannot be intercepted with panther.');
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
     * @test
     */
    public function html_head_assertions(): void
    {
        $this->markTestSkipped('Panther cannot access <head>.');
    }

    /**
     * @test
     */
    public function http_method_actions(): void
    {
        $this->markTestSkipped('Panther can only make "GET" requests.');
    }

    /**
     * @test
     */
    public function form_multiselect(): void
    {
        $this->markTestIncomplete('Do not yet have multi-select working with Panther.');
    }
}

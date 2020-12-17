<?php

namespace Zenstruck\Browser\Tests;

use Zenstruck\Browser\Tests\Component\EmailTests;
use Zenstruck\Browser\Tests\Extension\HttpTests;
use Zenstruck\Browser\Tests\Extension\JsonTests;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait BrowserKitBrowserTests
{
    use BrowserTests, EmailTests, HttpTests, JsonTests, ProfileAwareTests;

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
}

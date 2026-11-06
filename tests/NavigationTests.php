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

use PHPUnit\Framework\Attributes\Test;

/**
 * Navigations that must behave the same with and without Turbo.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait NavigationTests
{
    /**
     * @test
     */
    #[Test]
    public function can_follow_a_link(): void
    {
        $this->browser()
            ->visit('/nav')
            ->click('advance link')
            ->assertOn('/nav/target')
            ->assertSee('navigation target')
        ;
    }

    /**
     * @test
     */
    #[Test]
    public function can_follow_a_replace_link(): void
    {
        $this->browser()
            ->visit('/nav')
            ->click('replace link')
            ->assertOn('/nav/target')
            ->assertSee('navigation target')
        ;
    }

    /**
     * @test
     */
    #[Test]
    public function can_follow_a_link_with_a_fragment(): void
    {
        $this->browser()
            ->visit('/nav')
            ->click('fragment link')
            ->assertOn('/nav/target#section')
            ->assertSee('navigation target')
        ;
    }

    /**
     * @test
     */
    #[Test]
    public function can_follow_an_anchor_link(): void
    {
        $this->browser()
            ->visit('/nav')
            ->click('anchor link')
            ->assertOn('/nav#local')
            ->assertSee('navigation index')
        ;
    }

    /**
     * @test
     */
    #[Test]
    public function can_follow_a_redirecting_link(): void
    {
        $this->browser()
            ->visit('/nav')
            ->click('redirect link')
            ->assertOn('/nav/target?redirected=1')
            ->assertSeeIn('#query', 'redirected=1')
        ;
    }

    /**
     * @test
     */
    #[Test]
    public function can_follow_a_link_that_opts_out_of_turbo(): void
    {
        $this->browser()
            ->visit('/nav')
            ->click('opt out link')
            ->assertOn('/nav/target')
            ->assertSee('navigation target')
        ;
    }

    /**
     * @test
     */
    #[Test]
    public function following_a_download_link_stays_on_the_page(): void
    {
        $this->browser()
            ->visit('/nav')
            ->click('download link')
            ->assertOn('/nav')
            ->assertSee('navigation index')
        ;
    }

    /**
     * @test
     */
    #[Test]
    public function can_follow_a_link_to_a_page_that_requires_a_full_reload(): void
    {
        $this->browser()
            ->visit('/nav')
            ->click('reload link')
            ->assertOn('/nav/reload')
            ->assertSee('reloaded page')
        ;
    }

    /**
     * @test
     */
    #[Test]
    public function can_submit_a_get_form(): void
    {
        $this->browser()
            ->visit('/nav')
            ->fillField('Search', 'turbo')
            ->click('search')
            ->assertOn('/nav/search?q=turbo')
            ->assertSeeIn('#results', 'results for: turbo')
        ;
    }

    /**
     * @test
     */
    #[Test]
    public function can_submit_a_post_form_that_redirects(): void
    {
        $this->browser()
            ->visit('/nav')
            ->fillField('Title', 'my-title')
            ->click('create')
            ->assertOn('/nav/target?created=my-title')
            ->assertSeeIn('#query', 'created=my-title')
        ;
    }

    /**
     * @test
     */
    #[Test]
    public function can_submit_a_post_form_that_fails_validation(): void
    {
        // no url assertion: without Turbo the browser is on /nav/create, Turbo renders it on /nav
        $this->browser()
            ->visit('/nav')
            ->click('create')
            ->assertSeeIn('#error', 'title is required')
        ;
    }

    /**
     * @test
     */
    #[Test]
    public function can_submit_a_form_that_redirects_back_to_the_same_page(): void
    {
        $this->browser()
            ->visit('/nav/morph')
            ->assertSeeIn('#name', 'name: (none)')
            ->fillField('Name', 'kevin')
            ->click('save name')
            ->assertOn('/nav/morph')
            ->assertSeeIn('#name', 'name: kevin')
        ;
    }
}

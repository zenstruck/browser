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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

/**
 * Runs the whole Playwright suite again with Turbo loaded into every page.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @group playwright
 * @group turbo
 */
#[Group('playwright')]
#[Group('turbo')]
final class TurboPlaywrightBrowserTest extends PlaywrightBrowserTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['TURBO'] = '1';
    }

    protected function tearDown(): void
    {
        unset($_SERVER['TURBO']);

        parent::tearDown();
    }

    /**
     * @test
     */
    #[Test]
    public function turbo_is_loaded(): void
    {
        $this->browser()
            ->visit('/nav')
            ->assertSeeIn('#turbo-status', 'turbo active')
        ;
    }

    /**
     * @test
     */
    #[Test]
    public function visits_keep_permanent_elements(): void
    {
        $this->browser()
            ->visit('/nav')
            ->fillField('Permanent', 'kept')
            ->click('advance link')
            ->assertOn('/nav/target')
            ->assertFieldEquals('Permanent', 'kept')
        ;
    }

    /**
     * @test
     */
    #[Test]
    public function can_follow_a_turbo_method_link(): void
    {
        $this->browser()
            ->visit('/nav')
            ->click('delete link')
            ->assertOn('/nav/target?deleted=1')
            ->assertSeeIn('#query', 'deleted=1')
        ;
    }

    /**
     * @test
     */
    #[Test]
    public function turbo_confirm_dialogs_are_accepted(): void
    {
        $this->browser()
            ->visit('/nav')
            ->click('confirm link')
            ->assertOn('/nav/target')
            ->assertSee('navigation target')
        ;
    }

    /**
     * @test
     */
    #[Test]
    public function link_inside_a_frame_only_updates_the_frame(): void
    {
        $this->browser()
            ->visit('/nav/frames')
            ->click('frame link')
            ->assertOn('/nav/frames')
            ->assertSeeIn('#frame1', 'frame1 content loaded')
            ->assertSee('outside frame')
            ->assertNotSee('outside of the frames')
        ;
    }

    /**
     * @test
     */
    #[Test]
    public function link_outside_a_frame_can_target_it(): void
    {
        $this->browser()
            ->visit('/nav/frames')
            ->click('outside link targeting frame')
            ->assertOn('/nav/frames')
            ->assertSeeIn('#frame1', 'frame1 content loaded')
        ;
    }

    /**
     * @test
     */
    #[Test]
    public function link_inside_a_frame_can_target_the_whole_page(): void
    {
        $this->browser()
            ->visit('/nav/frames')
            ->click('top link')
            ->assertOn('/nav/target')
            ->assertSee('navigation target')
        ;
    }

    /**
     * @test
     */
    #[Test]
    public function advancing_frame_updates_the_url(): void
    {
        $this->browser()
            ->visit('/nav/frames')
            ->click('advancing frame link')
            ->assertOn('/nav/frames/content')
            ->assertSeeIn('#advance-frame', 'advance-frame content loaded')
            ->assertSee('outside frame')
        ;
    }

    /**
     * @test
     */
    #[Test]
    public function frame_missing_from_the_response_shows_an_error(): void
    {
        $this->browser()
            ->visit('/nav/frames')
            ->click('missing frame link')
            ->assertOn('/nav/frames')
            ->assertSeeIn('#missing-frame', 'Content missing')
        ;
    }

    /**
     * @test
     */
    #[Test]
    public function can_submit_a_form_inside_a_frame(): void
    {
        $this->browser()
            ->visit('/nav/frames')
            ->fillField('Frame Title', 'framed')
            ->click('save in frame')
            ->assertOn('/nav/frames')
            ->assertSeeIn('#form-frame', 'form-frame content loaded framed')
        ;
    }

    /**
     * @test
     */
    #[Test]
    public function eager_frame_loads_its_source(): void
    {
        $this->browser()
            ->visit('/nav/frames')
            ->assertSeeIn('#eager-frame', 'eager-frame content loaded')
        ;
    }

    /**
     * @test
     */
    #[Test]
    public function lazy_frame_loads_its_source_when_scrolled_into_view(): void
    {
        $this->browser()
            ->visit('/nav/frames')
            ->assertSeeIn('#lazy-frame', 'lazy frame loading')
            ->click('#lazy-frame')
            ->assertSeeIn('#lazy-frame', 'lazy-frame content loaded')
        ;
    }

    /**
     * @test
     *
     * @dataProvider streamActions
     */
    #[Test]
    #[DataProvider('streamActions')]
    public function form_response_can_stream_into_the_page(string $button, string $selector, string $expected): void
    {
        $this->browser()
            ->visit('/nav/streams')
            ->click($button)
            ->assertOn('/nav/streams')
            ->assertSeeIn($selector, $expected)
        ;
    }

    public static function streamActions(): iterable
    {
        yield 'append' => ['append stream', '#items li:last-child', 'appended item'];
        yield 'prepend' => ['prepend stream', '#items li:first-child', 'prepended item'];
        yield 'replace' => ['replace stream', '#item-1', 'replaced item'];
        yield 'update' => ['update stream', '#message', 'updated message'];
        yield 'before' => ['before stream', '#items li:first-child', 'before item'];
        yield 'after' => ['after stream', '#items li:nth-child(2)', 'after item'];
    }

    /**
     * @test
     */
    #[Test]
    public function form_response_can_stream_a_removal(): void
    {
        $this->browser()
            ->visit('/nav/streams')
            ->assertSeeElement('#item-2')
            ->click('remove stream')
            ->assertOn('/nav/streams')
            ->assertNotSeeElement('#item-2')
        ;
    }

    /**
     * @test
     */
    #[Test]
    public function form_response_can_stream_a_page_refresh(): void
    {
        $this->browser()
            ->visit('/nav/streams')
            ->assertSeeIn('#refreshes', 'refreshes: 0')
            ->click('refresh stream')
            ->assertOn('/nav/streams')
            ->assertSeeIn('#refreshes', 'refreshes: 1')
        ;
    }

    /**
     * @test
     */
    #[Test]
    public function stream_element_added_to_the_page_is_applied(): void
    {
        $this->browser()
            ->visit('/nav/streams')
            ->click('inline stream')
            ->assertSeeIn('#message', 'inline message')
        ;
    }
}

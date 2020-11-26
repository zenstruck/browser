<?php

namespace Zenstruck\Browser\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Browser\Test\Browser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class BrowserTest extends KernelTestCase
{
    use Browser;

    /**
     * @test
     */
    public function visit(): void
    {
        $this->browser()
            ->visit('/page1')
            ->assertSuccessful()
        ;
    }

    /**
     * @test
     */
    public function redirects_are_followed_by_default(): void
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     */
    public function can_intercept_redirects(): void
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     */
    public function exceptions_are_caught_by_default(): void
    {
        $this->browser()
            ->visit('/exception')
            ->assertStatus(500)
        ;
    }

    /**
     * @test
     */
    public function can_enable_exception_throwing(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('exception thrown');

        $this->browser()
            ->throwExceptions()
            ->visit('/exception')
        ;
    }
}

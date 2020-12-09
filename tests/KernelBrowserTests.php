<?php

namespace Zenstruck\Browser\Tests;

use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Browser\Test\HasKernelBrowser;
use Zenstruck\Browser\Tests\Extension\EmailTests;
use Zenstruck\Browser\Tests\Extension\JsonTests;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait KernelBrowserTests
{
    use BrowserKitBrowserTests, BrowserTests, EmailTests, HasKernelBrowser, JsonTests, ProfileAwareTests;

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

    /**
     * @test
     */
    public function can_enable_the_profiler(): void
    {
        $profile = $this->browser()
            ->withProfiling()
            ->visit('/page1')
            ->profile()
        ;

        $this->assertTrue($profile->hasCollector('request'));
    }

    protected static function browserClass(): string
    {
        return KernelBrowser::class;
    }
}

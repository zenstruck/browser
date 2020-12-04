<?php

namespace Zenstruck\Browser\Tests;

use Zenstruck\Browser\KernelBrowser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait KernelBrowserTests
{
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

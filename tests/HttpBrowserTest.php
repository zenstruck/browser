<?php

namespace Zenstruck\Browser\Tests;

use Symfony\Component\Panther\PantherTestCase;
use Zenstruck\Browser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class HttpBrowserTest extends PantherTestCase
{
    use BrowserTests;

    /**
     * @test
     */
    public function can_enable_exception_throwing(): void
    {
        $this->markTestSkipped('HttpBrowser cannot enable exception throwing.');
    }

    /**
     * @test
     */
    public function can_access_the_profiler(): void
    {
        $this->markTestSkipped('HttpBrowser cannot access the profiler... yet...');
    }

    protected function createBrowser(): Browser
    {
        return new Browser(static::createHttpBrowserClient());
    }
}

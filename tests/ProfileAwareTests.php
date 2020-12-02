<?php

namespace Zenstruck\Browser\Tests;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait ProfileAwareTests
{
    /**
     * @test
     */
    public function can_access_the_profiler(): void
    {
        $profile = $this->browser()
            ->visit('/page1')
            ->profile()
        ;

        $this->assertTrue($profile->hasCollector('request'));
    }
}

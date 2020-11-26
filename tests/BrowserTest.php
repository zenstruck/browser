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
}

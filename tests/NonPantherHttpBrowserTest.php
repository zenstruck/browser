<?php

namespace Zenstruck\Browser\Tests;

use PHPUnit\Framework\TestCase;
use Zenstruck\Browser\Test\HasHttpBrowser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class NonPantherHttpBrowserTest extends TestCase
{
    use HasHttpBrowser;

    /**
     * @test
     */
    public function can_navigate_symfony_site(): void
    {
        $this->browser()
            ->visit('/')
            ->follow('Documentation')
            ->assertSuccessful()
            ->assertOn('/doc/current/index.html')
        ;
    }

    protected function httpBrowserBaseUri(): string
    {
        return 'https://symfony.com';
    }
}

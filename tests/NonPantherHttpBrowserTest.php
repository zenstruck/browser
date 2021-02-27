<?php

namespace Zenstruck\Browser\Tests;

use PHPUnit\Framework\TestCase;
use Zenstruck\Browser\Test\HasBrowser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class NonPantherHttpBrowserTest extends TestCase
{
    use HasBrowser;

    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['HTTP_BROWSER_URI'] = 'https://symfony.com';
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($_SERVER['HTTP_BROWSER_URI']);
    }

    /**
     * @test
     */
    public function can_navigate_symfony_site(): void
    {
        $this->httpBrowser()
            ->visit('/')
            ->follow('Documentation')
            ->assertSuccessful()
            ->assertOn('/doc/current/index.html')
        ;
    }
}

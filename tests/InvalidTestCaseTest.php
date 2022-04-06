<?php

namespace Zenstruck\Browser\Tests;

use PHPUnit\Framework\TestCase;
use Zenstruck\Browser\Test\HasBrowser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class InvalidTestCaseTest extends TestCase
{
    use HasBrowser;

    /**
     * @test
     */
    public function cannot_create_browser(): void
    {
        $this->expectException(\LogicException::class);

        $this->browser();
    }

    /**
     * @test
     */
    public function cannot_create_panther_browser(): void
    {
        $this->expectException(\LogicException::class);

        $this->pantherBrowser();
    }

    /**
     * @test
     */
    public function cannot_create_api_browser(): void
    {
        $this->markTestIncomplete();
    }
}

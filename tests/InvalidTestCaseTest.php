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
}

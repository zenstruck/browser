<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Tests\Fixture;

use Zenstruck\Browser\Component;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TestComponent1 extends Component
{
    public function assertTitle(string $expected): void
    {
        $this->browser()->assertSeeIn('h1', $expected);
    }

    protected function preActions(): void
    {
        $this->browser()->visit('/page1');
    }

    protected function preAssertions(): void
    {
        $this->browser()->assertOn('/page1');
    }
}

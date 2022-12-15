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
final class TestComponent2 extends Component
{
    public bool $preActionsCalled = false;
    public bool $preAssertionsCalled = false;

    protected function preActions(): void
    {
        $this->preActionsCalled = true;
    }

    protected function preAssertions(): void
    {
        $this->preAssertionsCalled = true;
    }
}

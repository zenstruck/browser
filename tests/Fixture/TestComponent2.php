<?php

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

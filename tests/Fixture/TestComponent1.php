<?php

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

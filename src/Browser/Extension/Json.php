<?php

namespace Zenstruck\Browser\Extension;

use Zenstruck\Browser\Component\JsonComponent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait Json
{
    /**
     * @see JsonComponent::assertMatches()
     */
    final public function assertJsonMatches(string $expression, $expected): self
    {
        return $this->with(function(JsonComponent $component) use ($expression, $expected) {
            $component->assertMatches($expression, $expected);
        });
    }
}

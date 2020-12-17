<?php

namespace Zenstruck\Browser\Extension;

use PHPUnit\Framework\Assert as PHPUnit;
use function JmesPath\search;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait Json
{
    final public function assertJson(): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->responseHeaderContains('Content-Type', 'application/json')
        );
    }

    /**
     * @param string $expression JMESPath expression
     * @param mixed  $expected
     *
     * @return static
     */
    final public function assertJsonMatches(string $expression, $expected): self
    {
        $this->assertJson();

        $data = \json_decode($this->documentElement()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        PHPUnit::assertSame($expected, search($expression, $data));

        return $this;
    }
}

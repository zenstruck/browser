<?php

namespace Zenstruck\Browser\Extension;

use PHPUnit\Framework\Assert as PHPUnit;
use Zenstruck\Browser\Response\JsonResponse;

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
        if (!$this->response() instanceof JsonResponse) {
            PHPUnit::fail('Not a json response.');
        }

        PHPUnit::assertSame($expected, $this->response()->search($expression));

        return $this;
    }
}

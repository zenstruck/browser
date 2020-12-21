<?php

namespace Zenstruck\Browser\Extension;

use Zenstruck\Browser\Assert;
use Zenstruck\Browser\Response\JsonResponse;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait Json
{
    final public function assertJson(): self
    {
        Assert::wrapMinkExpectation(
            fn() => $this->webAssert()->responseHeaderContains('Content-Type', 'application/json')
        );

        return $this;
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
            Assert::fail('Not a json response.');
        }

        $actual = $this->response()->find($expression);

        Assert::true(
            $expected === $actual,
            'Expected: %s using JMESPath "%s" but found: %s.',
            \json_encode($expected, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            $expression,
            \json_encode($actual, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        );

        return $this;
    }
}

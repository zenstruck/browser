<?php

namespace Zenstruck\Browser\Assert;

use Zenstruck\Browser\Assert\Exception\AssertionFailed;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface Assertion
{
    /**
     * @throws AssertionFailed
     */
    public function __invoke(): void;
}

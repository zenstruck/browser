<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Session;

use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\WebAssert;
use Zenstruck\Assert as ZenstruckAssert;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 *
 * @mixin WebAssert
 */
final class Assert
{
    private WebAssert $webAssert;

    public function __construct(WebAssert $webAssert)
    {
        $this->webAssert = $webAssert;
    }

    /**
     * @param mixed[] $arguments
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        try {
            $ret = $this->webAssert->{$name}(...$arguments);
        } catch (ExpectationException $e) {
            ZenstruckAssert::fail($e->getMessage());
        }

        ZenstruckAssert::pass();

        return $ret;
    }
}

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
    /**
     * These read the response rather than the page: it cannot change while we poll, so retrying
     * them would only make their failures slower.
     */
    private const NOT_RETRYABLE = ['responseHeaderEquals', 'responseHeaderContains'];

    private WebAssert $webAssert;
    private AutoWait $autoWait;

    public function __construct(WebAssert $webAssert, AutoWait $autoWait)
    {
        $this->webAssert = $webAssert;
        $this->autoWait = $autoWait;
    }

    /**
     * @param mixed[] $arguments
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        try {
            $ret = $this->run($name, $arguments);
        } catch (ExpectationException $e) {
            ZenstruckAssert::fail($e->getMessage());
        }

        ZenstruckAssert::pass();

        return $ret;
    }

    /**
     * @param mixed[] $arguments
     */
    private function run(string $name, array $arguments): mixed
    {
        if (\in_array($name, self::NOT_RETRYABLE, true)) {
            return $this->webAssert->{$name}(...$arguments);
        }

        return $this->autoWait->assertion(fn() => $this->webAssert->{$name}(...$arguments));
    }
}

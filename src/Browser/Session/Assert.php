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

    public function __construct(WebAssert $webAssert, private ?\Closure $wait = null)
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
        $this->wait?->__invoke($name, $arguments);

        return $this->run($name, $arguments);
    }

    public function fieldValueContains(string $selector, string $expected, bool $negated = false): void
    {
        $this->wait?->__invoke('fieldValueContains', [$selector, $expected, $negated]);

        $field = $this->run('fieldExists', [$selector]);
        $assertion = ZenstruckAssert::that((array) $field->getValue());

        if ($negated) {
            $assertion->doesNotContain($expected);

            return;
        }

        $assertion->contains($expected);
    }

    public function elementVisible(string $selector, bool $negated = false): void
    {
        $this->wait?->__invoke('elementVisible', ['css', $selector, $negated]);

        try {
            $element = $this->webAssert->elementExists('css', $selector);
        } catch (ExpectationException $e) {
            if ($negated) {
                ZenstruckAssert::pass();

                return;
            }

            ZenstruckAssert::fail($e->getMessage());
        }

        ZenstruckAssert::true(
            $element->isVisible() !== $negated,
            $negated ? 'Expected element "%s" to not be visible but it is.' : 'Expected element "%s" to be visible but it isn\'t.',
            [$selector],
        );
    }

    private function run(string $name, array $arguments): mixed
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

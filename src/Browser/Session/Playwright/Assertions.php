<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Session\Playwright;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class Assertions
{
    public function __construct(private Wait $wait, private Selectors $selectors)
    {
    }

    public function __invoke(string $name, array $arguments): void
    {
        if (\str_starts_with($name, 'responseHeader')) {
            return;
        }

        if (\str_starts_with($name, 'element')) {
            $xpath = $this->selectors->xpath($arguments[0], $arguments[1]);
            $arguments = [$xpath, ...\array_slice($arguments, 2)];
        }

        if ('elementVisible' === $name) {
            $this->wait->element($arguments[0], $arguments[1] ? 'hidden' : 'visible');

            return;
        }

        if (\str_starts_with($name, 'field') || \str_starts_with($name, 'checkbox')) {
            $arguments[0] = $this->selectors->field($arguments[0]);
        }

        $this->wait->condition(\file_get_contents(__DIR__.'/assertion.js'), [$name, $arguments]);
    }
}

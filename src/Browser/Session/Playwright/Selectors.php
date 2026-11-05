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

use Behat\Mink\Selector\SelectorsHandler;
use Behat\Mink\Selector\Xpath\Manipulator;
use Symfony\Component\CssSelector\Exception\ExceptionInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class Selectors
{
    public function __construct(private SelectorsHandler $selectors)
    {
    }

    public function xpath(string $type, string|array $selector): string
    {
        $xpath = $this->selectors->selectorToXpath($type, $selector);

        return (new Manipulator())->prepend($xpath, '//html');
    }

    public function field(string $selector): string
    {
        return $this->firstMatch([
            $this->xpath('named_exact', ['field', $selector]),
            $this->xpath('named_partial', ['field', $selector]),
        ]);
    }

    public function clickable(string $selector): string
    {
        $candidates = [
            $this->xpath('named_exact', ['button', $selector]),
            $this->xpath('named_partial', ['button', $selector]),
            $this->xpath('named_exact', ['link', $selector]),
            $this->xpath('named_partial', ['link', $selector]),
        ];

        try {
            $candidates[] = $this->xpath('css', $selector);
        } catch (ExceptionInterface) {
            // A button or link label need not be a valid CSS selector.
        }

        return $this->firstMatch($candidates);
    }

    /**
     * @param string[] $candidates
     */
    private function firstMatch(array $candidates): string
    {
        $matches = [];
        $previous = [];

        foreach ($candidates as $candidate) {
            $guard = $previous ? '[not('.\implode(' | ', $previous).')]' : '';
            $matches[] = "({$candidate}){$guard}";
            $previous[] = $candidate;
        }

        return '('.\implode(' | ', $matches).')[1]';
    }
}

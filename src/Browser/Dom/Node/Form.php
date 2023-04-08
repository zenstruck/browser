<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Dom\Node;

use Zenstruck\Browser\Dom\Node;
use Zenstruck\Browser\Dom\Node\Form\Button;
use Zenstruck\Browser\Dom\Node\Form\Field;
use Zenstruck\Browser\Dom\Nodes;
use Zenstruck\Browser\Dom\Selector;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @phpstan-import-type SelectorType from Selector
 */
final class Form extends Node
{
    public const SELECTOR = 'form';

    /**
     * @param SelectorType $selector
     */
    public function fields(Selector|string|callable $selector = Field::SELECTOR): Nodes
    {
        return $this->descendents($selector);
    }

    public function buttons(): Nodes
    {
        return $this->descendents(Button::SELECTOR);
    }

    public function submitButtons(): Nodes
    {
        return $this->descendents('input[type="submit"],button[type="submit"]');
    }

    public function submitButton(): ?Button
    {
        return $this->submitButtons()->first()?->ensure(Button::class);
    }
}

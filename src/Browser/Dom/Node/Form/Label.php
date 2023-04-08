<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Dom\Node\Form;

use Zenstruck\Browser\Dom\Selector;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Label extends Element
{
    public const SELECTOR = 'label';

    public function field(): ?Field
    {
        if ($for = $this->attributes()->get('for')) {
            return $this->form()?->descendents(Selector::id($for))->first()?->ensure(Field::class);
        }

        // check if wrapping field
        return $this->descendents(Field::class)->first()?->ensure(Field::class);
    }
}

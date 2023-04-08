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

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Button extends Field
{
    public const SELECTOR = 'button,input[type="button"],input[type="submit"],input[type="reset"],input[type="image"]';

    public function type(): string
    {
        return $this->attributes()->get('type') ?? 'button';
    }

    public function value(): string
    {
        return $this->attributes()->get('value') ?? $this->text();
    }
}

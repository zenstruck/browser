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

use Zenstruck\Browser\Dom\Node;
use Zenstruck\Browser\Dom\Node\Form;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class Element extends Node
{
    public function form(): ?Form
    {
        return $this->ancestor('form')?->ensure(Form::class);
    }
}

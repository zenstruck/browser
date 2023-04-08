<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Dom\Node\Form\Field;

use Zenstruck\Browser\Dom\Node\Form\Field;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Checkbox extends Field
{
    public const SELECTOR = 'input[type="checkbox"]';

    public function isChecked(): bool
    {
        return $this->attributes()->has('checked');
    }

    /**
     * @return "on"|null
     */
    public function value(): ?string
    {
        return $this->isChecked() ? 'on' : null;
    }

    public function check(): void
    {
        $this->ensureSession()->select($this);
    }

    public function uncheck(): void
    {
        $this->ensureSession()->unselect($this);
    }
}

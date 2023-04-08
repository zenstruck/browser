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

use Zenstruck\Browser\Dom\Exception\RuntimeException;
use Zenstruck\Browser\Dom\Node\Form\Field;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Radio extends Field
{
    public const SELECTOR = 'input[type="radio"]';

    public function isSelected(): bool
    {
        return $this->attributes()->has('checked');
    }

    public function selected(): ?self
    {
        foreach ($this->collection() as $radio) {
            // hack for panther
            $radio = $radio->ensure(self::class);

            if ($radio->isSelected()) {
                return $radio;
            }
        }

        return null;
    }

    public function selectedValue(): ?string
    {
        return $this->selected()?->value();
    }

    public function select(?string $value = null): void
    {
        if (!$value) {
            $this->ensureSession()->select($this);

            return;
        }

        foreach ($this->collection() as $node) {
            $node = $node->ensure(self::class);

            if ($value === $node->value()) {
                $this->ensureSession()->select($node);

                return;
            }
        }

        throw new RuntimeException(\sprintf('Could not find radio with value "%s".', $value));
    }
}

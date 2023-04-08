<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Dom\Node\Form\Field\Select;

use Zenstruck\Browser\Dom\Exception\RuntimeException;
use Zenstruck\Browser\Dom\Node\Form\Field\Select;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Combobox extends Select
{
    public const SELECTOR = 'select:not([multiple])';

    public function selectedOption(): ?Option
    {
        foreach ($this->availableOptions() as $option) {
            // hack for panther
            $option = $option->ensure(Option::class);

            if ($option->isSelected()) {
                return $option;
            }
        }

        return null;
    }

    public function selectedValue(): ?string
    {
        return $this->selectedOption()?->value() ?? $this->availableOptions()->first()?->ensure(Option::class)->value() ?? null;
    }

    public function selectedText(): ?string
    {
        return $this->selectedOption()?->text() ?? $this->availableOptions()->first()?->text() ?? null;
    }

    public function value(): ?string
    {
        return $this->selectedValue();
    }

    public function select(string $value): void
    {
        if (!$option = $this->optionMatching($value)) {
            throw new RuntimeException(\sprintf('Could not find option with value/text "%s".', $value));
        }

        $option->select();
    }
}

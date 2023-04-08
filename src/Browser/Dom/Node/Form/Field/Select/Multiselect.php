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
use Zenstruck\Browser\Dom\Nodes;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Multiselect extends Select
{
    public const SELECTOR = 'select[multiple]';

    public function selectedOptions(): Nodes
    {
        return $this->descendents('option[selected]');
    }

    /**
     * @return string[]
     */
    public function selectedValues(): array
    {
        return \array_filter($this->selectedOptions()->map(fn(Option $option) => $option->value()));
    }

    /**
     * @return string[]
     */
    public function value(): array
    {
        return $this->selectedValues();
    }

    /**
     * @param string[] $values
     */
    public function select(array $values): void
    {
        foreach ($values as $value) {
            if ($option = $this->optionMatching($value)) {
                $option->select();

                continue;
            }

            throw new RuntimeException(\sprintf('Could not find option with value/text "%s".', $value));
        }
    }

    public function deselectAll(): void
    {
        $this->ensureSession()->unselect($this);
    }
}

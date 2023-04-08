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
use Zenstruck\Browser\Dom\Node\Form\Field\Select\Option;
use Zenstruck\Browser\Dom\Nodes;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class Select extends Field
{
    public const SELECTOR = 'select';

    public function availableOptions(): Nodes
    {
        return $this->descendents('option');
    }

    public function optionMatching(string $value): ?Option
    {
        $value = \mb_strtolower($value);

        // todo use xpath?
        return $this->optionEqualTo($value) ?? $this->optionContaining($value);
    }

    /**
     * @return string[]
     */
    public function availableValues(): array
    {
        return \array_filter($this->availableOptions()->map(fn(Option $option) => $option->value()));
    }

    public function isMultiple(): bool
    {
        return $this->attributes()->has('multiple');
    }

    private function optionEqualTo(string $value): ?Option
    {
        foreach ($this->availableOptions() as $option) {
            $option = $option->ensure(Option::class);

            if ($value === \mb_strtolower($option->value()) || \mb_strtolower($value) === $option->text()) {
                return $option;
            }
        }

        return null;
    }

    private function optionContaining(string $value): ?Option
    {
        foreach ($this->availableOptions() as $option) {
            $option = $option->ensure(Option::class);

            if (\str_contains(\mb_strtolower($option->value()), $value) || \str_contains(\mb_strtolower($option->text()), $value)) {
                return $option;
            }
        }

        return null;
    }
}

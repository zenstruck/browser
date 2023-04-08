<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Dom;

use Zenstruck\Assert;
use Zenstruck\Browser\Dom;
use Zenstruck\Browser\Dom\Node\Form\Field;
use Zenstruck\Browser\Dom\Node\Form\Field\Checkbox;
use Zenstruck\Browser\Dom\Node\Form\Field\Radio;
use Zenstruck\Browser\Dom\Node\Form\Field\Select\Combobox;
use Zenstruck\Browser\Dom\Node\Form\Field\Select\Multiselect;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @phpstan-import-type SelectorType from Selector
 */
trait Assertions
{
    public function contains(string $expected): static
    {
        Assert::that($this->dom()->crawler()->text())->contains($expected, strict: false);

        return $this;
    }

    public function doesNotContain(string $expected): static
    {
        Assert::that($this->dom()->crawler()->text())->doesNotContain($expected, strict: false);

        return $this;
    }

    /**
     * @param SelectorType $selector
     */
    public function containsIn(Selector|string|callable $selector, string $expected): static
    {
        Assert::that($this->node($selector)->text())->contains($expected, strict: false);

        return $this;
    }

    /**
     * @param SelectorType $selector
     */
    public function doesNotContainIn(Selector|string|callable $selector, string $expected): static
    {
        Assert::that($this->node($selector)->text())->doesNotContain($expected, strict: false);

        return $this;
    }

    /**
     * @param SelectorType $selector
     */
    public function elementIsVisible(Selector|string|callable $selector): static
    {
        Assert::true($this->node($selector)->isVisible(), 'Element with selector "{selector}" is not visible.', ['selector' => $selector]);

        return $this;
    }

    /**
     * @param SelectorType $selector
     */
    public function elementIsNotVisible(Selector|string|callable $selector): static
    {
        if (!$node = $this->dom()->find($selector)) {
            Assert::pass();

            return $this;
        }

        Assert::false($node->isVisible(), 'Element with selector "{selector}" is visible but it should not be.', ['selector' => $selector]);

        return $this;
    }

    /**
     * @param SelectorType $selector
     */
    public function hasElement(Selector|string|callable $selector): static
    {
        Assert::that($this->dom()->find($selector))->isNotNull('Element with selector "{selector}" does not exist.', ['selector' => $selector]);

        return $this;
    }

    /**
     * @param SelectorType $selector
     */
    public function doesNotHaveElement(Selector|string|callable $selector): static
    {
        Assert::that($this->dom()->find($selector))->isNull('Element with selector "{selector}" exists but it should not.', ['selector' => $selector]);

        return $this;
    }

    /**
     * @param SelectorType $selector
     */
    public function hasElementCount(Selector|string|callable $selector, int $count): static
    {
        Assert::that($this->dom()->findAll($selector))->hasCount($count, 'Expected {expected} elements with selector "{selector}" but found {actual}.', ['selector' => $selector]);

        return $this;
    }

    /**
     * @param SelectorType $selector
     */
    public function attributeContains(Selector|string|callable $selector, string $attribute, string $expected): static
    {
        Assert::that($value = $this->node($selector)->attributes()->get($attribute))
            ->isNotNull('Element with selector "{selector}" does not have attribute "{attribute}".', ['selector' => $selector, 'attribute' => $attribute])
            ->contains($expected, 'Element with selector "{selector}" attribute "{attribute}" does not contain "{expected}".', ['selector' => $selector, 'attribute' => $attribute, 'expected' => $expected], strict: false)
        ;

        return $this;
    }

    /**
     * @param SelectorType $selector
     */
    public function attributeDoesNotContain(Selector|string|callable $selector, string $attribute, string $expected): static
    {
        Assert::that($this->node($selector)->attributes()->get($attribute))
            ->doesNotContain($expected, 'Element with selector "{selector}" attribute "{attribute}" contains "{expected}" but it should not.', ['selector' => $selector, 'attribute' => $attribute, 'expected' => $expected], strict: false)
        ;

        return $this;
    }

    /**
     * @param SelectorType $selector
     */
    public function fieldEquals(Selector|string|callable $selector, string $expected): static
    {
        $field = $this->field($selector);

        if ($expected == $field->value()) {
            Assert::pass();

            return $this;
        }

        if ($field instanceof Combobox) {
            Assert::that($field->selectedText())
                ->equals($expected, 'Combobox with selector "{selector}" does not equal "{expected}".', ['selector' => $selector])
            ;

            return $this;
        }

        Assert::fail('Field with selector "{selector}" does not equal "{expected}".', ['selector' => $selector, 'expected' => $expected]);
    }

    /**
     * @param SelectorType $selector
     */
    public function fieldDoesNotEqual(Selector|string|callable $selector, string $expected): static
    {
        Assert::that($this->field($selector)->value())
            ->isNotEqualTo($expected, 'Field with selector "{selector}" equals "{expected}" but it should not.', ['selector' => $selector])
        ;

        return $this;
    }

    /**
     * @param SelectorType $selector
     */
    public function fieldSelected(Selector|string|callable $selector, string $expected): static
    {
        $field = $this->field($selector);

        switch ($field::class) {
            case Radio::class:
                Assert::that($field->selectedValue())
                    ->equals($expected, 'Radio with selector "{selector}" does not equal "{expected}".', ['selector' => $selector])
                ;

                break;

            case Multiselect::class:
                Assert::that($field->selectedValues())
                    ->contains($expected, 'Multiselect with selector "{selector}" does not have "{needle}" selected.', ['selector' => $selector])
                ;

                break;

            case Combobox::class:
                Assert::that($field->selectedValue())
                    ->is($expected, 'Combobox with selector "{selector}" has "{actual}" selected but expected "{expected}".', ['selector' => $selector])
                ;

                break;

            default:
                Assert::fail('Field with selector "{selector}" is not a radio, multiselect, or combobox.', ['selector' => $selector]);
        }

        return $this;
    }

    /**
     * @param SelectorType $selector
     */
    public function fieldNotSelected(Selector|string|callable $selector, string $expected): static
    {
        $field = $this->field($selector);

        switch ($field::class) {
            case Radio::class:
                Assert::that($field->isSelected())
                    ->is(false, 'Radio with selector "{selector}" is selected but it should not be.', ['selector' => $selector])
                ;

                break;

            case Multiselect::class:
                Assert::that($field->selectedValues())
                    ->doesNotContain($expected, 'Multiselect with selector "{selector}" has "{needle}" selected but it should not.', ['selector' => $selector])
                ;

                break;

            case Combobox::class:
                Assert::that($field->selectedValue())
                    ->isNot($expected, 'Combobox with selector "{selector}" has "{expected}" selected but it should not.', ['selector' => $selector])
                ;

                break;

            default:
                Assert::fail('Field with selector "{selector}" is not a radio, multiselect, or combobox.', ['selector' => $selector]);
        }

        return $this;
    }

    /**
     * @param SelectorType $selector
     */
    public function fieldChecked(Selector|string|callable $selector): static
    {
        $field = $this->field($selector);

        if ($field instanceof Checkbox) {
            Assert::that($field->isChecked())
                ->is(true, 'Checkbox with selector "{selector}" is not checked.', ['selector' => $selector])
            ;
        } elseif ($field instanceof Radio) {
            Assert::that($field->isSelected())
                ->is(true, 'Radio with selector "{selector}" is not selected.', ['selector' => $selector])
            ;
        } else {
            Assert::fail('Field with selector "{selector}" is not a checkbox or radio.', ['selector' => $selector]);
        }

        return $this;
    }

    /**
     * @param SelectorType $selector
     */
    public function fieldNotChecked(Selector|string|callable $selector): static
    {
        $field = $this->field($selector);

        if ($field instanceof Checkbox) {
            Assert::that($field->isChecked())
                ->is(false, 'Checkbox with selector "{selector}" is checked but it should not be.', ['selector' => $selector])
            ;
        } elseif ($field instanceof Radio) {
            Assert::that($field->isSelected())
                ->is(false, 'Radio with selector "{selector}" is selected but it should not be.', ['selector' => $selector])
            ;
        } else {
            Assert::fail('Field with selector "{selector}" is not a checkbox or radio.', ['selector' => $selector]);
        }

        return $this;
    }

    /**
     * @template N as Node
     *
     * @param SelectorType    $selector
     * @param class-string<N> $type
     *
     * @return N
     */
    private function node(Selector|string|callable $selector, string $type = Node::class): Node
    {
        if (!$node = $this->dom()->find($selector)) {
            Assert::fail('Could not find node with selector "{selector}".', ['selector' => $selector]);
        }

        return Assert::try(static fn() => $node->ensure($type));
    }

    /**
     * @param SelectorType $selector
     */
    private function field(Selector|string|callable $selector): Field
    {
        return $this->node(Selector::field($selector), Field::class);
    }

    abstract private function dom(): Dom;
}

<?php

namespace Zenstruck\Browser\Dom\Form\Field;

use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\WebDriverSelect;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\Panther\DomCrawler\Crawler as PantherCrawler;
use Zenstruck\Browser\Dom\Form\Field;

/**
 * @mixin ChoiceFormField
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ChoiceField extends Field
{
    public function tick(): void
    {
        if ($this->is('radio')) {
            $this->setValue(null);

            return;
        }

        $this->inner->tick();
    }

    public function untick(): void
    {
        if ($this->is('radio')) {
            throw new \InvalidArgumentException('Radio fields cannot be unchecked.');
        }

        $this->inner->untick();
    }

    public function setValue($value): void
    {
        if ($this->is('radio')) {
            $value = $value ?? $this->attr('value');
        }

        if (null === $value) {
            throw new \InvalidArgumentException('Value required for select form fields.');
        }

        if (\is_array($value) && !$this->isMultiple()) {
            throw new \InvalidArgumentException('Value for a single select form field cannot be an array.');
        }

        if (\is_array($value) && $this->isMultiple() && $this->dom->inner() instanceof PantherCrawler) {
            // todo have a separate PantherCrawler to avoid the "inner" check above
            // with panther, unselect all before setting (otherwise existing selections will remain)
            (new WebDriverSelect($this->dom->getElement(0)))->deselectAll();
        }

        try {
            $this->inner->setValue($value);
        } catch (NoSuchElementException $e) {
            // try selecting by visible text
            $select = new WebDriverSelect($this->dom->getElement(0));

            foreach ((array) $value as $item) {
                $select->selectByVisibleText($item);
            }
        }
    }

    /**
     * @param array|string $type
     */
    public function is($type): bool
    {
        return \in_array($this->getType(), (array) $type, true);
    }
}

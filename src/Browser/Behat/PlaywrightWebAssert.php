<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Behat;

use Behat\Mink\Element\ElementInterface;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Element\TraversableElement;
use Behat\Mink\Session;
use Behat\Mink\WebAssert;
use Zenstruck\Browser\Behat\Driver\PlaywrightDriver;
use Zenstruck\Browser\Session\Playwright\Selectors;

/**
 * Mink 1.10 declares scalar parameters without types.
 *
 * @internal
 */
final class PlaywrightWebAssert extends WebAssert
{
    public function __construct(Session $session, private readonly PlaywrightDriver $driver)
    {
        parent::__construct($session);
    }

    public function addressEquals($page): void
    {
        $this->waitForAddress($page, false);
        parent::addressEquals($page);
    }

    public function addressNotEquals($page): void
    {
        $this->waitForAddress($page, true);
        parent::addressNotEquals($page);
    }

    public function pageTextContains($text): void
    {
        $this->check(__FUNCTION__, [$text]);
    }

    public function pageTextNotContains($text): void
    {
        $this->check(__FUNCTION__, [$text]);
    }

    public function responseContains($text): void
    {
        $this->check(__FUNCTION__, [$text]);
    }

    public function responseNotContains($text): void
    {
        $this->check(__FUNCTION__, [$text]);
    }

    public function elementsCount($selectorType, $selector, $count, ?ElementInterface $container = null): void
    {
        $xpath = $this->elementXpath($selectorType, $selector, $container);
        $this->check(__FUNCTION__, [$selectorType, $selector, $count, $container], [$xpath, $count]);
    }

    public function elementExists($selectorType, $selector, ?ElementInterface $container = null): NodeElement
    {
        $xpath = $this->elementXpath($selectorType, $selector, $container);

        return $this->check(__FUNCTION__, [$selectorType, $selector, $container], [$xpath]);
    }

    public function elementNotExists($selectorType, $selector, ?ElementInterface $container = null): void
    {
        $xpath = $this->elementXpath($selectorType, $selector, $container);
        $this->check(__FUNCTION__, [$selectorType, $selector, $container], [$xpath]);
    }

    public function elementTextContains($selectorType, $selector, $text): void
    {
        $xpath = $this->elementXpath($selectorType, $selector);
        $this->check(__FUNCTION__, [$selectorType, $selector, $text], [$xpath, $text]);
    }

    public function elementTextNotContains($selectorType, $selector, $text): void
    {
        $xpath = $this->elementXpath($selectorType, $selector);
        $this->check(__FUNCTION__, [$selectorType, $selector, $text], [$xpath, $text]);
    }

    public function elementContains($selectorType, $selector, $html): void
    {
        $xpath = $this->elementXpath($selectorType, $selector);
        $this->check(__FUNCTION__, [$selectorType, $selector, $html], [$xpath, $html]);
    }

    public function elementNotContains($selectorType, $selector, $html): void
    {
        $xpath = $this->elementXpath($selectorType, $selector);
        $this->check(__FUNCTION__, [$selectorType, $selector, $html], [$xpath, $html]);
    }

    public function elementAttributeContains($selectorType, $selector, $attribute, $text): void
    {
        $xpath = $this->elementXpath($selectorType, $selector);
        $this->check(__FUNCTION__, [$selectorType, $selector, $attribute, $text], [$xpath, $attribute, $text]);
    }

    public function elementAttributeNotContains($selectorType, $selector, $attribute, $text): void
    {
        $xpath = $this->elementXpath($selectorType, $selector);
        $this->check(__FUNCTION__, [$selectorType, $selector, $attribute, $text], [$xpath, $attribute, $text]);
    }

    public function fieldExists($field, ?TraversableElement $container = null): NodeElement
    {
        $xpath = $this->fieldXpath($field, $container);

        return $this->check(__FUNCTION__, [$field, $container], [$xpath]);
    }

    public function fieldNotExists($field, ?TraversableElement $container = null): void
    {
        $xpath = $this->fieldXpath($field, $container);
        $this->check(__FUNCTION__, [$field, $container], [$xpath]);
    }

    public function fieldValueEquals($field, $value, ?TraversableElement $container = null): void
    {
        $xpath = $this->fieldXpath($field, $container);
        $this->check(__FUNCTION__, [$field, $value, $container], [$xpath, $value]);
    }

    public function fieldValueNotEquals($field, $value, ?TraversableElement $container = null): void
    {
        $xpath = $this->fieldXpath($field, $container);
        $this->check(__FUNCTION__, [$field, $value, $container], [$xpath, $value]);
    }

    public function checkboxChecked($field, ?TraversableElement $container = null): void
    {
        $xpath = $this->fieldXpath($field, $container);
        $this->check(__FUNCTION__, [$field, $container], [$xpath]);
    }

    public function checkboxNotChecked($field, ?TraversableElement $container = null): void
    {
        $xpath = $this->fieldXpath($field, $container);
        $this->check(__FUNCTION__, [$field, $container], [$xpath]);
    }

    private function elementXpath(string $type, string|array $selector, ?ElementInterface $container = null): string
    {
        $selectors = $this->selectors($container);

        return $selectors->xpath($type, $selector);
    }

    private function fieldXpath(string $field, ?TraversableElement $container): string
    {
        $selectors = $this->selectors($container);

        return $selectors->field($field);
    }

    private function selectors(?ElementInterface $container): Selectors
    {
        $handler = $this->session->getSelectorsHandler();

        return new Selectors($handler, $container?->getXpath() ?? '//html');
    }

    private function check(string $assertion, array $arguments, ?array $predicateArguments = null): mixed
    {
        $this->driver->waitForAssertion($assertion, $predicateArguments ?? $arguments);

        // Mink assertions call one another, so use a plain WebAssert to wait only once.
        $assert = new WebAssert($this->session);

        return $assert->{$assertion}(...$arguments);
    }

    private function waitForAddress(string $page, bool $negated): void
    {
        $address = $this->cleanUrl($page);
        $this->driver->waitForCondition(<<<'JS'
            ([expected, negated]) => {
                const path = location.pathname.replace(/^\/[^./]+\.php\//, '/');
                const fragment = location.hash === '#0' ? '' : location.hash;
                return (path + fragment === expected) !== negated;
            }
            JS, [$address, $negated]);
    }
}

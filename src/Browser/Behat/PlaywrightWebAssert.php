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
 * @internal
 */
final class PlaywrightWebAssert extends WebAssert
{
    public function __construct(Session $session, private readonly PlaywrightDriver $driver)
    {
        parent::__construct($session);
    }

    public function addressEquals(string $page): void
    {
        $this->waitForAddress($page, false);
        parent::addressEquals($page);
    }

    public function addressNotEquals(string $page): void
    {
        $this->waitForAddress($page, true);
        parent::addressNotEquals($page);
    }

    public function pageTextContains(string $text): void
    {
        $this->check(__FUNCTION__, [$text]);
    }

    public function pageTextNotContains(string $text): void
    {
        $this->check(__FUNCTION__, [$text]);
    }

    public function responseContains(string $text): void
    {
        $this->check(__FUNCTION__, [$text]);
    }

    public function responseNotContains(string $text): void
    {
        $this->check(__FUNCTION__, [$text]);
    }

    public function elementsCount(string $selectorType, $selector, int $count, ?ElementInterface $container = null): void
    {
        $xpath = $this->elementXpath($selectorType, $selector, $container);
        $this->check(__FUNCTION__, [$selectorType, $selector, $count, $container], [$xpath, $count]);
    }

    public function elementExists(string $selectorType, $selector, ?ElementInterface $container = null): NodeElement
    {
        $xpath = $this->elementXpath($selectorType, $selector, $container);

        return $this->check(__FUNCTION__, [$selectorType, $selector, $container], [$xpath]);
    }

    public function elementNotExists(string $selectorType, $selector, ?ElementInterface $container = null): void
    {
        $xpath = $this->elementXpath($selectorType, $selector, $container);
        $this->check(__FUNCTION__, [$selectorType, $selector, $container], [$xpath]);
    }

    public function elementTextContains(string $selectorType, $selector, string $text): void
    {
        $xpath = $this->elementXpath($selectorType, $selector);
        $this->check(__FUNCTION__, [$selectorType, $selector, $text], [$xpath, $text]);
    }

    public function elementTextNotContains(string $selectorType, $selector, string $text): void
    {
        $xpath = $this->elementXpath($selectorType, $selector);
        $this->check(__FUNCTION__, [$selectorType, $selector, $text], [$xpath, $text]);
    }

    public function elementContains(string $selectorType, $selector, string $html): void
    {
        $xpath = $this->elementXpath($selectorType, $selector);
        $this->check(__FUNCTION__, [$selectorType, $selector, $html], [$xpath, $html]);
    }

    public function elementNotContains(string $selectorType, $selector, string $html): void
    {
        $xpath = $this->elementXpath($selectorType, $selector);
        $this->check(__FUNCTION__, [$selectorType, $selector, $html], [$xpath, $html]);
    }

    public function elementAttributeContains(string $selectorType, $selector, string $attribute, string $text): void
    {
        $xpath = $this->elementXpath($selectorType, $selector);
        $this->check(__FUNCTION__, [$selectorType, $selector, $attribute, $text], [$xpath, $attribute, $text]);
    }

    public function elementAttributeNotContains(string $selectorType, $selector, string $attribute, string $text): void
    {
        $xpath = $this->elementXpath($selectorType, $selector);
        $this->check(__FUNCTION__, [$selectorType, $selector, $attribute, $text], [$xpath, $attribute, $text]);
    }

    public function fieldExists(string $field, ?TraversableElement $container = null): NodeElement
    {
        $xpath = $this->fieldXpath($field, $container);

        return $this->check(__FUNCTION__, [$field, $container], [$xpath]);
    }

    public function fieldNotExists(string $field, ?TraversableElement $container = null): void
    {
        $xpath = $this->fieldXpath($field, $container);
        $this->check(__FUNCTION__, [$field, $container], [$xpath]);
    }

    public function fieldValueEquals(string $field, string $value, ?TraversableElement $container = null): void
    {
        $xpath = $this->fieldXpath($field, $container);
        $this->check(__FUNCTION__, [$field, $value, $container], [$xpath, $value]);
    }

    public function fieldValueNotEquals(string $field, string $value, ?TraversableElement $container = null): void
    {
        $xpath = $this->fieldXpath($field, $container);
        $this->check(__FUNCTION__, [$field, $value, $container], [$xpath, $value]);
    }

    public function checkboxChecked(string $field, ?TraversableElement $container = null): void
    {
        $xpath = $this->fieldXpath($field, $container);
        $this->check(__FUNCTION__, [$field, $container], [$xpath]);
    }

    public function checkboxNotChecked(string $field, ?TraversableElement $container = null): void
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

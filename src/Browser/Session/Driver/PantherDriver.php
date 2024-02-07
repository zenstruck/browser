<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Session\Driver;

use Behat\Mink\Exception\DriverException;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Interactions\Internal\WebDriverCoordinates;
use Facebook\WebDriver\Internal\WebDriverLocatable;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverSelect;
use Symfony\Component\BrowserKit\Exception\BadMethodCallException;
use Symfony\Component\DomCrawler\Field\FormField;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\DomCrawler\Crawler;
use Symfony\Component\Panther\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\Panther\DomCrawler\Field\FileFormField;
use Symfony\Component\Panther\DomCrawler\Field\InputFormField;
use Symfony\Component\Panther\DomCrawler\Field\TextareaFormField;
use Zenstruck\Browser\HttpOptions;
use Zenstruck\Browser\Session\Driver;

/**
 * @ref https://github.com/robertfausk/mink-panther-driver
 *
 * @author Robert Freigang <robertfreigang@gmx.de>
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 *
 * @method Client client()
 */
final class PantherDriver extends Driver
{
    private const EMPTY_FILE_VALUE = [
        'name' => '',
        'type' => '',
        'tmp_name' => '',
        'error' => 4,
        'size' => 0,
    ];

    public function __construct(Client $client)
    {
        parent::__construct($client);
    }

    public function request(string $method, string $url, HttpOptions $options): void
    {
        if ('GET' !== $method) {
            throw new UnsupportedDriverActionException('%s only supports "GET" requests.', $this);
        }

        $this->client()->request('GET', $this->prepareUrl($url));
    }

    public function quit(): void
    {
        $this->client()->quit();
    }

    public function getCurrentUrl(): string
    {
        return $this->client()->getCurrentURL();
    }

    public function getContent(): string
    {
        return $this->client()->getWebDriver()->getPageSource();
    }

    public function getText($xpath): string
    {
        $crawler = $this->filteredCrawler($xpath);

        if (($element = $crawler->getElement(0)) && 'title' === $element->getTagName()) {
            // hack to get the text of the title html element
            // for this element, WebDriverElement::getText() returns an empty string
            // the only way to get the value is to get title from the client
            return $this->client()->getTitle();
        }

        return \trim($crawler->text(null, true));
    }

    /**
     * @return array|bool|string|null
     */
    public function getValue($xpath)
    {
        try {
            $formField = $this->formField($xpath);
            $value = $formField->getValue();

            if ('' === $value && $formField instanceof ChoiceFormField) {
                $value = null;
            }
        } catch (DriverException $e) {
            // e.g. element is an option
            $element = $this->crawlerElement($this->filteredCrawler($xpath));
            $value = $element->getAttribute('value');
        }

        return $value;
    }

    public function setValue($xpath, $value): void
    {
        $element = $this->crawlerElement($this->filteredCrawler($xpath));
        $jsNode = $this->jsNode($xpath);

        if (!$value && 'select' === $element->getTagName() && $element->getAttribute('multiple')) {
            $this->executeScript(\sprintf('%s.selectedIndex = -1', $jsNode));
        }

        if ('input' === $element->getTagName() && \in_array($element->getAttribute('type'), ['date', 'time', 'color'])) {
            $this->executeScript(\sprintf('%s.value = \'%s\'', $jsNode, $value));
        } else {
            try {
                $formField = $this->formField($xpath);
                $formField->setValue($value);
            } catch (DriverException $e) {
                // e.g. element is on option
                $element->sendKeys($value);
            }
        }

        // Remove the focus from the element if the field still has focus in
        // order to trigger the change event. By doing this instead of simply
        // triggering the change event for the given xpath we ensure that the
        // change event will not be triggered twice for the same element if it
        // has lost focus in the meanwhile. If the element has lost focus
        // already then there is nothing to do as this will already have caused
        // the triggering of the change event for that element.
        if ($this->evaluateScript(\sprintf('document.activeElement === %s', $jsNode))) {
            $this->executeScript('document.activeElement.blur();');
        }
    }

    public function getTagName($xpath): string
    {
        return $this->crawlerElement($this->filteredCrawler($xpath))->getTagName();
    }

    public function check($xpath): void
    {
        $this->choiceFormField($xpath)->tick();
    }

    public function uncheck($xpath): void
    {
        $this->choiceFormField($xpath)->untick();
    }

    public function selectOption($xpath, $value, $multiple = false): void
    {
        try {
            $this->choiceFormField($xpath)->select($value);

            return;
        } catch (NoSuchElementException $e) {
        }

        // try selecting by visible text
        $select = new WebDriverSelect($this->crawlerElement($this->filteredCrawler($xpath)));
        $select->selectByVisibleText($value);
    }

    /**
     * @param string $path
     */
    public function attachFile($xpath, $path): void
    {
        $field = $this->fileFormField($xpath);

        if (self::EMPTY_FILE_VALUE === $field->getValue()) {
            // first file
            $field->upload($path);

            return;
        }

        if (!$this->filteredCrawler($xpath)->attr('multiple')) {
            throw new \InvalidArgumentException('Cannot attach multiple files to a non-multiple file field.');
        }

        $field->upload($path);
    }

    public function isChecked($xpath): bool
    {
        $element = $this->crawlerElement($this->filteredCrawler($xpath));

        if ('radio' === \mb_strtolower((string) $element->getAttribute('type'))) {
            return null !== $element->getAttribute('checked');
        }

        return $this->choiceFormField($xpath)->hasValue();
    }

    public function click($xpath): void
    {
        $this->client()->getMouse()->click($this->toCoordinates($xpath));
        $this->client()->refreshCrawler();
    }

    public function doubleClick($xpath): void
    {
        $this->client()->getMouse()->doubleClick($this->toCoordinates($xpath));
        $this->client()->refreshCrawler();
    }

    public function rightClick($xpath): void
    {
        $this->client()->getMouse()->contextClick($this->toCoordinates($xpath));
        $this->client()->refreshCrawler();
    }

    public function executeScript($script): void
    {
        if (\preg_match('/^function[\s(]/', $script)) {
            $script = \preg_replace('/;$/', '', $script);
            $script = '('.$script.')';
        }

        $this->client()->executeScript($script);
    }

    public function evaluateScript($script)
    {
        if (0 !== \mb_strpos(\trim($script), 'return ')) {
            $script = 'return '.$script;
        }

        return $this->client()->executeScript($script);
    }

    public function getHtml($xpath): string
    {
        // cut the tag itself (making innerHTML out of outerHTML)
        return (string) \preg_replace('/^<[^>]+>|<[^>]+>$/', '', $this->getOuterHtml($xpath));
    }

    public function isVisible($xpath): bool
    {
        return $this->crawlerElement($this->filteredCrawler($xpath))->isDisplayed();
    }

    public function getOuterHtml($xpath): string
    {
        return $this->filteredCrawler($xpath)->html();
    }

    public function getAttribute($xpath, $name): ?string
    {
        return $this->crawlerElement($this->filteredCrawler($xpath))->getAttribute($name);
    }

    protected function findElementXpaths($xpath): array
    {
        $nodes = $this->crawler()->filterXPath($xpath);

        $elements = [];

        foreach ($nodes as $i => $node) {
            $elements[] = \sprintf('(%s)[%d]', $xpath, $i + 1);
        }

        return $elements;
    }

    private function crawlerElement(Crawler $crawler): WebDriverElement
    {
        if (null !== $node = $crawler->getElement(0)) {
            return $node;
        }

        throw new DriverException('The element does not exist');
    }

    private function prepareUrl(string $url): string
    {
        return (string) \preg_replace('#(https?://[^/]+)(/[^/.]+\.php)?#', '$1$2', $url);
    }

    private function filteredCrawler(string $xpath): Crawler
    {
        if (!\count($crawler = $this->crawler()->filterXPath($xpath))) {
            throw new DriverException(\sprintf('There is no element matching XPath "%s"', $xpath));
        }

        return $crawler;
    }

    private function crawler(): Crawler
    {
        try {
            return $this->client()->getCrawler();
        } catch (BadMethodCallException $e) {
            throw new DriverException('Unable to access the response content before visiting a page', 0, $e);
        }
    }

    private function formField(string $xpath): FormField
    {
        try {
            return $this->choiceFormField($xpath);
        } catch (DriverException $e) {
            try {
                return $this->inputFormField($xpath);
            } catch (DriverException $e) {
                try {
                    return $this->fileFormField($xpath);
                } catch (DriverException $e) {
                    return $this->textareaFormField($xpath);
                }
            }
        }
    }

    private function choiceFormField(string $xpath): ChoiceFormField
    {
        $element = $this->crawlerElement($this->filteredCrawler($xpath));

        try {
            return new ChoiceFormField($element);
        } catch (\LogicException $e) {
            throw new DriverException(\sprintf('Impossible to get the element with XPath "%s" as it is not a choice form field. %s', $xpath, $e->getMessage()));
        }
    }

    private function inputFormField(string $xpath): InputFormField
    {
        $element = $this->crawlerElement($this->filteredCrawler($xpath));

        try {
            return new InputFormField($element);
        } catch (\LogicException $e) {
            throw new DriverException(\sprintf('Impossible to check the element with XPath "%s" as it is not an input form field.', $xpath));
        }
    }

    private function fileFormField(string $xpath): FileFormField
    {
        $element = $this->crawlerElement($this->filteredCrawler($xpath));

        try {
            return new FileFormField($element);
        } catch (\LogicException $e) {
            throw new DriverException(\sprintf('Impossible to check the element with XPath "%s" as it is not a file form field.', $xpath));
        }
    }

    private function textareaFormField(string $xpath): TextareaFormField
    {
        $element = $this->crawlerElement($this->filteredCrawler($xpath));

        try {
            return new TextareaFormField($element);
        } catch (\LogicException $e) {
            throw new DriverException(\sprintf('Impossible to check the element with XPath "%s" as it is not a textarea.', $xpath));
        }
    }

    private function toCoordinates(string $xpath): WebDriverCoordinates
    {
        $element = $this->crawlerElement($this->filteredCrawler($xpath));

        if (!$element instanceof WebDriverLocatable) {
            throw new \RuntimeException(\sprintf('The element of "%s" xpath selector does not implement "%s".', $xpath, WebDriverLocatable::class));
        }

        return $element->getCoordinates();
    }

    private function jsNode(string $xpath): string
    {
        return "document.evaluate(`{$xpath}`, document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue";
    }
}

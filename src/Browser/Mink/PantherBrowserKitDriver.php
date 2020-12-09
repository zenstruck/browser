<?php

namespace Zenstruck\Browser\Mink;

use Behat\Mink\Driver\CoreDriver;
use Behat\Mink\Exception\DriverException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Interactions\Internal\WebDriverCoordinates;
use Facebook\WebDriver\Internal\WebDriverLocatable;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverSelect;
use Symfony\Component\DomCrawler\Field\FormField;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\DomCrawler\Crawler;
use Symfony\Component\Panther\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\Panther\DomCrawler\Field\FileFormField;
use Symfony\Component\Panther\DomCrawler\Field\InputFormField;
use Symfony\Component\Panther\DomCrawler\Field\TextareaFormField;

/**
 * @credit https://github.com/robertfausk/mink-panther-driver
 *
 * @author Robert Freigang <robertfreigang@gmx.de>
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PantherBrowserKitDriver extends CoreDriver
{
    private Client $client;
    private bool $started = false;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function start(): void
    {
        $this->started = true;
    }

    public function stop(): void
    {
        $this->started = false;
    }

    public function isStarted(): bool
    {
        return $this->started;
    }

    public function reset(): void
    {
        $this->client->restart();
    }

    public function visit($url): void
    {
        $this->client->request('GET', $this->prepareUrl($url));
    }

    public function getCurrentUrl(): string
    {
        return $this->client->getCurrentURL();
    }

    public function getContent(): string
    {
        return $this->client->getWebDriver()->getPageSource();
    }

    public function getText($xpath): string
    {
        $text = $this->filteredCrawler($xpath)->text();
        $text = \str_replace("\n", ' ', $text);
        $text = \preg_replace('/ {2,}/', ' ', $text);

        return \trim($text);
    }

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

    public function setValue($xpath, $value)
    {
        $element = $this->crawlerElement($this->filteredCrawler($xpath));
        $jsNode = $this->jsNode($xpath);

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

    public function attachFile($xpath, $path): void
    {
        $this->fileFormField($xpath)->upload($path);
    }

    public function isChecked($xpath): bool
    {
        return $this->choiceFormField($xpath)->hasValue();
    }

    public function click($xpath): void
    {
        $this->client->getMouse()->click($this->toCoordinates($xpath));
        $this->client->refreshCrawler();
    }

    public function executeScript($script)
    {
        if (\preg_match('/^function[\s(]/', $script)) {
            $script = \preg_replace('/;$/', '', $script);
            $script = '('.$script.')';
        }

        return $this->client->executeScript($script);
    }

    public function evaluateScript($script)
    {
        if (0 !== \mb_strpos(\trim($script), 'return ')) {
            $script = 'return '.$script;
        }

        return $this->client->executeScript($script);
    }

    public function getHtml($xpath): string
    {
        // cut the tag itself (making innerHTML out of outerHTML)
        return \preg_replace('/^<[^>]+>|<[^>]+>$/', '', $this->getOuterHtml($xpath));
    }

    public function getOuterHtml($xpath): string
    {
        $crawler = $this->filteredCrawler($xpath);

        return $crawler->html();
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
        return \preg_replace('#(https?://[^/]+)(/[^/.]+\.php)?#', '$1$2', $url);
    }

    private function filteredCrawler($xpath): Crawler
    {
        if (!\count($crawler = $this->crawler()->filterXPath($xpath))) {
            throw new DriverException(\sprintf('There is no element matching XPath "%s"', $xpath));
        }

        return $crawler;
    }

    private function crawler(): Crawler
    {
        if (null === $crawler = $this->client->getCrawler()) {
            throw new DriverException('Unable to access the response content before visiting a page');
        }

        return $crawler;
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

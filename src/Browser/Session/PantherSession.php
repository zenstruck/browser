<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Session;

use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Interactions\Internal\WebDriverCoordinates;
use Facebook\WebDriver\Internal\WebDriverLocatable;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverSelect;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\DomCrawler\Crawler;
use Symfony\Component\Panther\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\Panther\DomCrawler\Field\FileFormField;
use Symfony\Component\Panther\DomCrawler\Field\InputFormField;
use Symfony\Component\Panther\DomCrawler\Field\TextareaFormField;
use Zenstruck\Browser\Session;
use Zenstruck\Dom;
use Zenstruck\Dom\Exception\RuntimeException;
use Zenstruck\Dom\Node;
use Zenstruck\Dom\Node\Form\Field\Checkbox;
use Zenstruck\Dom\Node\Form\Field\File;
use Zenstruck\Dom\Node\Form\Field\Input;
use Zenstruck\Dom\Node\Form\Field\Radio;
use Zenstruck\Dom\Node\Form\Field\Select\Multiselect;
use Zenstruck\Dom\Node\Form\Field\Select\Option;
use Zenstruck\Dom\Node\Form\Field\Textarea;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 * @author Robert Freigang <robertfreigang@gmx.de>
 *
 * @internal
 *
 * @method Client client()
 */
final class PantherSession extends Session
{
    public function __construct(Client $client)
    {
        parent::__construct($client);
    }

    public function dom(): Dom
    {
        return new Dom($this->client()->getCrawler(), $this);
    }

    public function content(): string
    {
        return $this->client()->getPageSource();
    }

    public function currentUrl(): string
    {
        return $this->client()->getCurrentURL();
    }

    public function click(Node $node): void
    {
        $this->client()->getMouse()->click($this->coordinatesFor($node));
        $this->client()->refreshCrawler();
    }

    public function select(Option|Radio|Checkbox $node): void
    {
        if ($node instanceof Checkbox) {
            (new ChoiceFormField($this->elementFor($node)))->tick();

            return;
        }

        if ($node instanceof Radio) {
            (new ChoiceFormField($this->elementFor($node)))->select($node->value());

            return;
        }

        $element = $this->elementFor($node->selector() ?? throw new RuntimeException('Unable to find "select" for "option".'));
        $field = new ChoiceFormField($element);

        try {
            $field->select($node->value());
        } catch (NoSuchElementException $e) {
            // try selecting by visible text
            (new WebDriverSelect($element))->selectByVisibleText($node->value());
        }
    }

    public function unselect(Checkbox|Multiselect $node): void
    {
        $element = $this->elementFor($node);

        if ($node instanceof Multiselect) {
            (new WebDriverSelect($element))->deselectAll();

            return;
        }

        (new ChoiceFormField($element))->untick();
    }

    public function attach(File $node, array $filenames): void
    {
        $field = new FileFormField($this->elementFor($node));

        foreach ($filenames as $filename) {
            $field->upload($filename);
        }
    }

    public function fill(Textarea|Input $node, string $value): void
    {
        if ($node instanceof Textarea) {
            (new TextareaFormField($this->elementFor($node)))->setValue($value);

            return;
        }

        (new InputFormField($this->elementFor($node)))->setValue($value);
    }

    public function doubleClick(Node $node): void
    {
        $this->client()->getMouse()->doubleClick($this->coordinatesFor($node));
        $this->client()->refreshCrawler();
    }

    public function rightClick(Node $node): void
    {
        $this->client()->getMouse()->contextClick($this->coordinatesFor($node));
        $this->client()->refreshCrawler();
    }

    private function coordinatesFor(Node $node): WebDriverCoordinates
    {
        $element = $this->elementFor($node);

        if (!$element instanceof WebDriverLocatable) {
            throw new RuntimeException('The web driver element is not locatable.');
        }

        return $element->getCoordinates();
    }

    private function elementFor(Node $node): WebDriverElement
    {
        $crawler = $node->crawler();

        \assert($crawler instanceof Crawler);

        return $crawler->getElement(0) ?? throw new RuntimeException('Unable to find the web driver element.');
    }
}

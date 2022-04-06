<?php

namespace Zenstruck\Browser;

use Behat\Mink\Element\DocumentElement;
use Behat\Mink\Element\NodeElement;
use Zenstruck\Assert;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Selector
{
    private const SEPARATOR = '__:__';
    private const SEPARATOR_FORMAT = '%s'.self::SEPARATOR.'%s';
    private const CSS = 'css';
    private const ID = 'id';
    private const BUTTON = 'button';
    private const LINK = 'link';
    private const XPATH = 'xpath';
    private const FIELD = 'field';
    private const TYPES = [self::CSS, self::ID, self::XPATH, self::BUTTON, self::LINK, self::FIELD];
    private const AUTO_TYPES = [self::CSS, self::ID, self::BUTTON, self::LINK, self::FIELD];

    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * @param string $value css, xpath, button(id, value or alt), link(id, title, text or image alt), input(id, name or label)
     */
    public static function wrap(string $value): self
    {
        return new self($value);
    }

    public static function css(string $locator): self
    {
        return self::create(self::CSS, $locator);
    }

    public static function id(string $locator): self
    {
        return self::create(self::ID, \ltrim($locator, '#'));
    }

    /**
     * @param string $locator button id, value or alt
     */
    public static function button(string $locator): self
    {
        return self::create(self::BUTTON, $locator);
    }

    /**
     * @param string $locator link id, title, text or image alt
     */
    public static function link(string $locator): self
    {
        return self::create(self::LINK, $locator);
    }

    public static function xpath(string $locator): self
    {
        return self::create(self::XPATH, $locator);
    }

    /**
     * @param string $locator input id, name or label
     */
    public static function field(string $locator): self
    {
        return self::create(self::FIELD, $locator);
    }

    public function find(DocumentElement $page): NodeElement
    {
        if (\count($items = $this->findAll($page))) {
            return \current($items);
        }

        Assert::fail('Could not find element matching "%s".', [$this->parse()[1]]);
    }

    /**
     * @internal
     *
     * @return NodeElement[]
     */
    public function findAll(DocumentElement $page): array
    {
        [$type, $locator] = $this->parse();

        if (\in_array($type, self::TYPES, true)) {
            return $this->findAllForType($page, $type, $locator);
        }

        foreach (self::AUTO_TYPES as $try) {
            if (\count($elements = $this->findAllForType($page, $try, $locator))) {
                return $elements;
            }
        }

        return [];
    }

    private static function create(string $type, string $locator): self
    {
        return new self(\sprintf(self::SEPARATOR_FORMAT, $type, $locator));
    }

    /**
     * @return NodeElement[]
     */
    private function findAllForType(DocumentElement $page, string $type, string $locator): array
    {
        switch ($type) {
            case self::CSS:
            case self::XPATH:
                return $page->findAll($type, $locator);
            case self::ID:
                return $page->findAll('named', ['id', $locator]);
            case self::FIELD:
                return $page->findAll('named', ['field', $locator]);
            case self::LINK:
                return $page->findAll('named', ['link', $locator]);
            case self::BUTTON:
                return $page->findAll('named', ['button', $locator]);
        }

        return [];
    }

    /**
     * @return array{0:string,1:string}
     */
    private function parse(): array
    {
        if (1 === \count($parts = \explode(self::SEPARATOR, $this->value, 2))) {
            return ['auto', $parts[0]];
        }

        return [$parts[0], $parts[1]];
    }
}

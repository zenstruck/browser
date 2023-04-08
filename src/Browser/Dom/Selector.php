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

use Symfony\Component\DomCrawler\Crawler;
use Zenstruck\Browser\Dom;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @phpstan-type SelectorType = self|string|callable(Crawler):(Node|Nodes|Crawler|null)
 */
final class Selector implements \Stringable
{
    private const TYPE_AUTO = 'auto';
    private const TYPE_CSS = 'css';
    private const TYPE_ID = 'id';
    private const TYPE_FIELD = 'field';
    private const TYPE_FIELD_FOR_NAME = 'field-for-name';
    private const TYPE_FIELD_FOR_LABEL = 'field-for-label';
    private const TYPE_CLICKABLE = 'clickable';
    private const TYPE_LINK = 'link';
    private const TYPE_BUTTON = 'button';
    private const TYPE_IMAGE = 'image';
    private const TYPE_XPATH = 'xpath';
    private const TYPE_CALLBACK = '(callback)';
    private const SEPARATOR = ':==:';
    private const SEPARATOR_FORMAT = '%s'.self::SEPARATOR.'%s';
    private const VALID_TYPES = [
        self::TYPE_AUTO,
        self::TYPE_CSS,
        self::TYPE_ID,
        self::TYPE_FIELD,
        self::TYPE_FIELD_FOR_NAME,
        self::TYPE_FIELD_FOR_LABEL,
        self::TYPE_CLICKABLE,
        self::TYPE_LINK,
        self::TYPE_BUTTON,
        self::TYPE_IMAGE,
        self::TYPE_XPATH,
    ];
    private const PRIORITY_MAP = [
        self::TYPE_AUTO => [self::TYPE_CSS, self::TYPE_BUTTON, self::TYPE_LINK, self::TYPE_IMAGE, self::TYPE_ID, self::TYPE_FIELD_FOR_NAME, self::TYPE_FIELD_FOR_LABEL],
        self::TYPE_CLICKABLE => [self::TYPE_BUTTON, self::TYPE_LINK, self::TYPE_ID, self::TYPE_CSS, self::TYPE_IMAGE, self::TYPE_FIELD_FOR_NAME, self::TYPE_FIELD_FOR_LABEL],
        self::TYPE_FIELD => [self::TYPE_FIELD_FOR_NAME, self::TYPE_FIELD_FOR_LABEL, self::TYPE_ID, self::TYPE_CSS],
    ];

    /**
     * @param self::TYPE_* $type
     */
    private function __construct(private string $type, private string|\Closure $value)
    {
    }

    public function __toString(): string
    {
        if ($this->value instanceof \Closure) {
            return self::TYPE_CALLBACK;
        }

        return \sprintf(self::SEPARATOR_FORMAT, $this->type, $this->value);
    }

    /**
     * @param SelectorType $value
     */
    public static function wrap(self|string|callable $value): self
    {
        return self::createForDefault(self::TYPE_AUTO, $value);
    }

    /**
     * @param SelectorType $value
     */
    public static function css(self|string|callable $value): self
    {
        return self::createForDefault(self::TYPE_CSS, $value);
    }

    /**
     * @param SelectorType $value
     */
    public static function id(self|string|callable $value): self
    {
        return self::createForDefault(self::TYPE_ID, $value);
    }

    /**
     * @param SelectorType $value
     */
    public static function field(self|string|callable $value): self
    {
        return self::createForDefault(self::TYPE_FIELD, $value);
    }

    /**
     * @param SelectorType $value
     */
    public static function fieldForName(self|string|callable $value): self
    {
        return self::createForDefault(self::TYPE_FIELD_FOR_NAME, $value);
    }

    /**
     * @param SelectorType $value
     */
    public static function fieldForLabel(self|string|callable $value): self
    {
        return self::createForDefault(self::TYPE_FIELD_FOR_LABEL, $value);
    }

    /**
     * @param SelectorType $value
     */
    public static function clickable(self|string|callable $value): self
    {
        return self::createForDefault(self::TYPE_CLICKABLE, $value);
    }

    /**
     * @param SelectorType $value
     */
    public static function link(self|string|callable $value): self
    {
        return self::createForDefault(self::TYPE_LINK, $value);
    }

    /**
     * @param SelectorType $value
     */
    public static function button(self|string|callable $value): self
    {
        return self::createForDefault(self::TYPE_BUTTON, $value);
    }

    /**
     * @param SelectorType $value
     */
    public static function image(self|string|callable $value): self
    {
        return self::createForDefault(self::TYPE_IMAGE, $value);
    }

    /**
     * @param SelectorType $value
     */
    public static function xpath(self|string|callable $value): self
    {
        return self::createForDefault(self::TYPE_XPATH, $value);
    }

    /**
     * @internal
     */
    public function filter(Crawler $crawler): Crawler
    {
        if ($this->value instanceof \Closure) {
            $return = ($this->value)(new Dom($crawler));

            return match (true) {
                $return instanceof Crawler => $return,
                $return instanceof Node, $return instanceof Nodes => $return->crawler(),
                null === $return => new Crawler(),
                default => throw new \LogicException(\sprintf('Invalid return type from selector callback, expected %s|%s|%s|null, got "%s".', Node::class, Nodes::class, Crawler::class, \get_debug_type($return))),
            };
        }

        $types = self::PRIORITY_MAP[$this->type] ?? [$this->type];

        foreach ($types as $type) {
            try {
                $filtered = self::filterByType($crawler, $type, $this->value);
            } catch (\Throwable) {
                $filtered = new Crawler();
            }

            if (\count($filtered)) {
                return $filtered;
            }
        }

        return new Crawler();
    }

    private static function filterByType(Crawler $crawler, string $type, string $value): Crawler
    {
        return match ($type) {
            self::TYPE_CSS => $crawler->filter($value),
            self::TYPE_ID => $crawler->filter(\sprintf('#%s', \ltrim($value, '#'))),
            self::TYPE_LINK => self::filterLink($crawler, $value),
            self::TYPE_BUTTON => $crawler->selectButton($value),
            self::TYPE_IMAGE => $crawler->selectImage($value),
            self::TYPE_FIELD_FOR_NAME => $crawler->filter(\sprintf('input[name="%1$s"],select[name="%1$s"],textarea[name="%1$s"]', $value)),
            self::TYPE_FIELD_FOR_LABEL => self::filterFieldForLabel($crawler, $value),
            self::TYPE_XPATH => $crawler->filterXPath($value),
            default => throw new \InvalidArgumentException(\sprintf('Invalid type "%s".', $type)),
        };
    }

    private static function filterLink(Crawler $crawler, string $value): Crawler
    {
        if (\count($link = $crawler->selectLink($value))) {
            return $link;
        }

        // try with partial link text
        if (\count($link = self::filterByType($crawler, self::TYPE_XPATH, self::xpathContains('a', $value)))) {
            return $link;
        }

        // try with title tag
        return self::filterByType($crawler, self::TYPE_XPATH, self::xpathContains('a', $value, 'title'));
    }

    private static function filterFieldForLabel(Crawler $crawler, string $value): Crawler
    {
        // find exact label match
        $label = self::filterByType($crawler, self::TYPE_XPATH, self::xpathEquals('label', $value));

        if (!\count($label)) {
            // find partial label match
            $label = self::filterByType($crawler, self::TYPE_XPATH, self::xpathContains('label', $value));
        }

        if (!\count($label)) {
            return new Crawler();
        }

        if ($id = $label->attr('for')) {
            return self::filterByType($crawler, self::TYPE_ID, $id);
        }

        // try and find field nested in label
        return $label->filter('input,select,textarea');
    }

    /**
     * @param self::TYPE_* $type
     * @param SelectorType $value
     */
    private static function createForDefault(string $type, self|string|callable $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        if (\is_callable($value) && !\is_string($value)) {
            return new self(self::TYPE_CALLBACK, \Closure::fromCallable($value));
        }

        if (1 === \count($parts = \explode(self::SEPARATOR, $value, 2))) {
            return new self($type, $value);
        }

        if (!\in_array($parts[0], self::VALID_TYPES, true)) {
            $parts[0] = $type;
        }

        return new self($parts[0], $parts[1]);
    }

    private static function xpathEquals(string $element, string $value, ?string $attribute = null): string
    {
        return \sprintf('descendant-or-self::%s[%s = "%s"]', $element, self::xpathNormalize($attribute), \mb_strtolower($value));
    }

    private static function xpathContains(string $element, string $value, ?string $attribute = null): string
    {
        return \sprintf('descendant-or-self::%s[contains(%s, "%s")]', $element, self::xpathNormalize($attribute), \mb_strtolower($value));
    }

    private static function xpathNormalize(?string $attribute): string
    {
        return \sprintf(
            'translate(normalize-space(%s), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")',
            $attribute ? '@'.\ltrim($attribute, '@') : '.',
        );
    }
}

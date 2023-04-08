<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Dom\Node;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @implements \IteratorAggregate<string, string>
 */
final class Attributes implements \IteratorAggregate, \Countable
{
    public function __construct(private \DOMElement $element)
    {
    }

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        return \iterator_to_array($this);
    }

    /**
     * @return string[]
     */
    public function classes(): array
    {
        return \array_filter(\array_map('trim', \explode(' ', $this->get('class') ?? '')));
    }

    public function has(string $name): bool
    {
        return $this->element->hasAttribute($name);
    }

    public function get(string $name): ?string
    {
        if (!$this->has($name)) {
            return null;
        }

        return $this->element->getAttribute($name);
    }

    public function is(string $name, string ...$oneOf): bool
    {
        if (!$value = $this->get($name)) {
            return false;
        }

        $value = \mb_strtolower($value);

        foreach ($oneOf as $expected) {
            if (\mb_strtolower($expected) === $value) {
                return true;
            }
        }

        return false;
    }

    public function getIterator(): \Traversable
    {
        foreach ($this->element->attributes as $attribute) {
            yield $attribute->name => $attribute->value;
        }
    }

    public function count(): int
    {
        return $this->element->attributes->count();
    }
}

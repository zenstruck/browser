<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Dom\Node\Form\Field\Select;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Panther\DomCrawler\Crawler as PantherCrawler;
use Zenstruck\Browser\Dom\Node\Form\Field;
use Zenstruck\Browser\Dom\Node\Form\Field\Select;
use Zenstruck\Browser\Dom\Nodes;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Option extends Field
{
    public const SELECTOR = 'option';

    public function value(): string
    {
        return $this->attributes()->get('value') ?? $this->text();
    }

    public function isSelected(): bool
    {
        return $this->attributes()->has('selected');
    }

    public function collection(): Nodes
    {
        return $this->selector()?->availableOptions() ?? Nodes::create(new Crawler(), $this->session);
    }

    public function selector(): ?Select
    {
        if (!$this->crawler() instanceof PantherCrawler) {
            return $this->ancestor('select')?->ensure(Select::class);
        }

        foreach ($this->ancestors() as $ancestor) {
            // hack for panther - the above code doesn't work as expected - returns first select in document
            if ('select' === $ancestor->tag()) {
                return $ancestor->ensure(Select::class);
            }
        }

        return null;
    }

    public function select(): void
    {
        $this->ensureSession()->select($this);
    }
}

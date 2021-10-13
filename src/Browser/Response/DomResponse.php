<?php

namespace Zenstruck\Browser\Response;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\VarDumper\VarDumper;
use Zenstruck\Browser\Dom;
use Zenstruck\Browser\Response;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class DomResponse extends Response
{
    private Dom $dom;

    final public function crawler(): Crawler
    {
        return $this->dom()->crawler();
    }

    final public function dom(): Dom
    {
        return $this->dom ??= Dom::wrap(new Crawler($this->body(), 'http://localhost'));
    }

    final public function dump(?string $selector = null): void
    {
        if (null === $selector) {
            parent::dump();

            return;
        }

        $elements = $this->crawler()->filter($selector);

        if (0 === $elements->count()) {
            throw new \RuntimeException("Element \"{$selector}\" not found.");
        }

        $elements->each(function(Crawler $node) {
            VarDumper::dump($node->outerHtml());
        });
    }
}

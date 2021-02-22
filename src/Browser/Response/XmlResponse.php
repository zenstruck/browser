<?php

namespace Zenstruck\Browser\Response;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\VarDumper\VarDumper;
use Zenstruck\Browser\Response;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class XmlResponse extends Response
{
    public function crawler(): Crawler
    {
        $dom = new \DOMDocument();
        $dom->loadXML($this->body());

        return new Crawler($dom);
    }

    public function dump(?string $selector = null): void
    {
        if (null === $selector) {
            parent::dump();

            return;
        }

        $elements = $this->crawler()->filter($selector);

        if (!\count($elements)) {
            throw new \RuntimeException("Element \"{$selector}\" not found.");
        }

        foreach ($elements as $element) {
            VarDumper::dump($element->textContent);
        }
    }
}

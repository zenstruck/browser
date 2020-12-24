<?php

namespace Zenstruck\Browser\Response;

use Behat\Mink\Element\NodeElement;
use Symfony\Component\VarDumper\VarDumper;
use Zenstruck\Browser\Response;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class HtmlResponse extends Response
{
    public function dump(?string $selector = null): void
    {
        if (null === $selector) {
            parent::dump();

            return;
        }

        $elements = $this->session()->getPage()->findAll('css', $selector);
        $elements = \array_map(static fn(NodeElement $node) => $node->getHtml(), $elements);

        if (empty($elements)) {
            throw new \RuntimeException("Element \"{$selector}\" not found.");
        }

        foreach ($elements as $element) {
            VarDumper::dump($element);
        }
    }

    final public function find(string $selector): string
    {
        if (!$element = $this->session()->getPage()->find('css', $selector)) {
            throw new \RuntimeException("Element \"{$selector}\" not found.");
        }

        return $element->getHtml();
    }
}

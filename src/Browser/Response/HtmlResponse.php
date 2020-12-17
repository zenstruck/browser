<?php

namespace Zenstruck\Browser\Response;

use Zenstruck\Browser\Response;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class HtmlResponse extends Response
{
    final public function find(string $selector): string
    {
        if (!$element = $this->session()->getPage()->find('css', $selector)) {
            throw new \RuntimeException("Element \"{$selector}\" not found.");
        }

        return $element->getHtml();
    }
}

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
 */
final class Expectation
{
    use Assertions;

    private Dom $dom;

    public function __construct(string|Dom|Crawler $dom)
    {
        $this->dom = $dom instanceof Dom ? $dom : new Dom($dom);
    }

    private function dom(): Dom
    {
        return $this->dom;
    }
}

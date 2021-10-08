<?php

namespace Zenstruck\Browser\Response;

use Symfony\Component\DomCrawler\Crawler;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class XmlResponse extends DomResponse
{
    public function crawler(): Crawler
    {
        return new Crawler($this->body());
    }
}

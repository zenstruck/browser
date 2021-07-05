<?php

namespace Zenstruck\Browser\Response;

use Symfony\Component\DomCrawler\Crawler;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class HtmlResponse extends DomResponse
{
    public function crawler(): Crawler
    {
        $crawler = new Crawler();
        $crawler->addHtmlContent($this->body());

        return $crawler;
    }
}

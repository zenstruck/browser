<?php

namespace Zenstruck\Browser\Response;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class PantherResponse extends HtmlResponse
{
    protected function rawMetadata(): string
    {
        return "URL: {$this->session()->getCurrentUrl()}\n";
    }
}

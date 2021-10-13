<?php

namespace Zenstruck\Browser\Response;

use Symfony\Component\HttpFoundation\HeaderBag;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PantherResponse extends HtmlResponse
{
    /**
     * @internal
     */
    public function statusCode(): int
    {
        throw new \BadMethodCallException('Panther does not support accessing the status code.');
    }

    /**
     * @internal
     */
    public function headers(): HeaderBag
    {
        throw new \BadMethodCallException('Panther does not support accessing response headers.');
    }

    protected function rawMetadata(): string
    {
        return "URL: {$this->session()->getCurrentUrl()}\n";
    }
}

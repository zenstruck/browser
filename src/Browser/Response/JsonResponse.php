<?php

namespace Zenstruck\Browser\Response;

use Symfony\Component\VarDumper\VarDumper;
use Zenstruck\Browser\Response;
use function JmesPath\search;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class JsonResponse extends Response
{
    public function json()
    {
        if (empty($this->body())) {
            return null;
        }

        return \json_decode($this->body(), true, 512, \JSON_THROW_ON_ERROR);
    }

    public function dump(?string $selector = null): void
    {
        if (null === $selector) {
            parent::dump();
        } else {
            VarDumper::dump($this->search($selector));
        }
    }

    public function search(string $selector)
    {
        return search($selector, $this->json());
    }

    protected function rawBody(): string
    {
        return \json_encode($this->json(), \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR);
    }
}

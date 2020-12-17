<?php

namespace Zenstruck\Browser\Response;

use Zenstruck\Browser\Response;
use function JmesPath\search;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class JsonResponse extends Response
{
    public function body()
    {
        return \json_decode(parent::body(), true, 512, JSON_THROW_ON_ERROR);
    }

    public function find(string $selector)
    {
        return search($selector, $this->body());
    }

    protected function rawBody(): string
    {
        return \json_encode($this->body(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}

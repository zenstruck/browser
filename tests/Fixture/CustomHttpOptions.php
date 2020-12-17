<?php

namespace Zenstruck\Browser\Tests\Fixture;

use Zenstruck\Browser\Extension\Http\HttpOptions;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CustomHttpOptions extends HttpOptions
{
    public static function api(string $token, $json = null): self
    {
        return static::json($json)->withHeader('X-Token', $token);
    }
}

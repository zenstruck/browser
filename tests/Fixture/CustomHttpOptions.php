<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Tests\Fixture;

use Zenstruck\Browser\HttpOptions;

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

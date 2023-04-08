<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class Dumper
{
    private function __construct()
    {
    }

    public static function dump(mixed $what): void
    {
        \function_exists('dump') ? dump($what) : \var_dump($what);
    }
}

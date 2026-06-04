<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Bridge\Behat\Kernel;

use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\PantherTestCase;

/**
 * Tiny adapter that extends {@see PantherTestCase} solely to gain access to its
 * `protected static` `createPantherClient()` / `createAdditionalPantherClient()`
 * methods. Used by the Behat kernel booters because Panther's PHPUnit-rooted
 * API can't be called from outside the test-case hierarchy.
 *
 * @author Hugo Hamon <hello@kodero.fr>
 */
final class PantherClientFactory extends PantherTestCase
{
    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $kernelOptions
     * @param array<string, mixed> $managerOptions
     */
    public static function createPrimary(array $options = [], array $kernelOptions = [], array $managerOptions = []): Client
    {
        return self::createPantherClient($options, $kernelOptions, $managerOptions);
    }

    public static function createAdditional(): Client
    {
        return self::createAdditionalPantherClient();
    }
}

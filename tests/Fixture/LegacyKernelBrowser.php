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
use Zenstruck\Browser\KernelBrowser;

class LegacyKernelBrowser extends KernelBrowser
{
    public function post(string $url, $options = []): self
    {
        $options = HttpOptions::create($options);
        $options->withHeader('X-Legacy', 'yes');

        return parent::post($url, $options);
    }

    public function configureDefaults(): self
    {
        $this->defaultHttpOptions = HttpOptions::create(['headers' => ['X-Legacy' => 'configured']]);

        return $this;
    }
}

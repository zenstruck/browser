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

use Symfony\Bundle\FrameworkBundle\KernelBrowser as SymfonyKernelBrowser;
use Zenstruck\Browser;
use Zenstruck\Browser\Internal\ApiMethods;
use Zenstruck\Browser\Internal\KernelBrowserBehavior;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @method SymfonyKernelBrowser client()
 */
class ApiBrowser extends Browser
{
    use ApiMethods;
    use KernelBrowserBehavior;

    public function saveCurrentState(string $filename): void
    {
        $type = (string) $this->session()->getResponseHeader('content-type');
        $extension = 'txt';

        if (\str_contains($type, 'json')) {
            $extension = 'json';
        }

        if (\str_contains($type, 'xml')) {
            $extension = 'xml';
        }

        $this->saveSource("{$filename}.{$extension}");
    }

    protected function useParameters(): array
    {
        return [...parent::useParameters(), ...$this->apiParameters()];
    }
}

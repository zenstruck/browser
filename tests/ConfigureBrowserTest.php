<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Browser\Test\HasBrowser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ConfigureBrowserTest extends WebTestCase
{
    use HasBrowser;

    /**
     * @test
     */
    public function browser_has_been_configured(): void
    {
        $this->page1Browser()->assertOn('/page1');
    }

    protected function page1Browser(): KernelBrowser
    {
        return $this->browser()->visit('/page1');
    }
}

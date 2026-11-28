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

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Browser\Test\HasBrowser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ConfigureBrowserTest extends WebTestCase
{
    use HasBrowser {
        browser as baseBrowser;
    }

    /**
     * @test
     */
    #[Test]
    public function browser_has_been_configured(): void
    {
        $this->page1Browser()->assertOn('/page1');
    }

    /**
     * @test
     */
    #[Test]
    public function html_browser_uses_the_existing_browser_override(): void
    {
        $this->htmlBrowser()->assertOn('/page1');
    }

    protected function browser(array $options = [], array $server = []): KernelBrowser
    {
        return $this->baseBrowser($options, $server)->visit('/page1');
    }

    protected function page1Browser(): KernelBrowser
    {
        return $this->browser();
    }
}

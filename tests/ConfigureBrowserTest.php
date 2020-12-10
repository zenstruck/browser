<?php

namespace Zenstruck\Browser\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser;
use Zenstruck\Browser\Test\HasKernelBrowser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ConfigureBrowserTest extends WebTestCase
{
    use HasKernelBrowser;

    /**
     * @test
     */
    public function browser_has_been_configured(): void
    {
        $this->browser()->assertOn('/page1');
    }

    protected function configureBrowser(Browser $browser): void
    {
        $browser->visit('/page1');
    }
}

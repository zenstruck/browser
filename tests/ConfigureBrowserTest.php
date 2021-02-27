<?php

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

    public function page1Browser(): KernelBrowser
    {
        return $this->browser()->visit('/page1');
    }
}

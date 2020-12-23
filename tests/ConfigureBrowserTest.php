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
    use HasBrowser {
        kernelBrowser as baseKernelBrowser;
    }

    /**
     * @test
     */
    public function browser_has_been_configured(): void
    {
        $this->kernelBrowser()->assertOn('/page1');
    }

    protected function kernelBrowser(): KernelBrowser
    {
        return $this->baseKernelBrowser()->visit('/page1');
    }
}

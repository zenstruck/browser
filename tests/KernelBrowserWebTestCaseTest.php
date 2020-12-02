<?php

namespace Zenstruck\Browser\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Extension\Email;
use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Browser\Test\HasKernelBrowser;
use Zenstruck\Browser\Tests\Extension\EmailTests;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class KernelBrowserWebTestCaseTest extends WebTestCase
{
    use HasKernelBrowser, BrowserTests, ProfileAwareTests, KernelBrowserTests, EmailTests;

    /**
     * @test
     */
    public function calling_browser_ensures_kernel_is_shutdown(): void
    {
        static::bootKernel();

        $this->browser()
            ->visit('/page1')
            ->assertSuccessful()
        ;
    }

    protected function createEmailBrowser(): KernelBrowser
    {
        return new class(static::createClient()) extends KernelBrowser {
            use Email;
        };
    }
}

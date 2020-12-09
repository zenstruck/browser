<?php

namespace Zenstruck\Browser\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Browser\Extension\Email;
use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Browser\Test\HasKernelBrowser;
use Zenstruck\Browser\Tests\Extension\EmailTests;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class KernelBrowserKernelTestCaseTest extends KernelTestCase
{
    use BrowserTests, EmailTests, HasKernelBrowser, KernelBrowserTests, ProfileAwareTests;

    protected function createEmailBrowser(): KernelBrowser
    {
        static::bootKernel();

        return new class(static::$container->get('test.client')) extends KernelBrowser {
            use Email;
        };
    }
}

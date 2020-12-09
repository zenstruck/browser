<?php

namespace Zenstruck\Browser\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Browser\Extension\Email;
use Zenstruck\Browser\Extension\Json;
use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Browser\Test\HasKernelBrowser;
use Zenstruck\Browser\Tests\Extension\EmailTests;
use Zenstruck\Browser\Tests\Extension\JsonTests;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class KernelBrowserKernelTestCaseTest extends KernelTestCase
{
    use BrowserTests, EmailTests, HasKernelBrowser, JsonTests, KernelBrowserTests, ProfileAwareTests;

    protected function createEmailBrowser(): KernelBrowser
    {
        static::bootKernel();

        return new class(static::$container->get('test.client')) extends KernelBrowser {
            use Email;
        };
    }

    protected function createJsonBrowser(): KernelBrowser
    {
        static::bootKernel();

        return new class(static::$container->get('test.client')) extends KernelBrowser {
            use Json;
        };
    }
}

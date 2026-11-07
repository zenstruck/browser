<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Tests\Behat;

use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Assert;
use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Browser\PlaywrightBrowser;
use Zenstruck\Browser\Test\Behat\BrowserContext;
use Zenstruck\Browser\Test\Behat\Driver\KernelDriver;
use Zenstruck\Browser\Test\Behat\Driver\PlaywrightDriver;

final class FeatureContext extends BrowserContext
{
    public function __construct(private readonly KernelInterface $kernel)
    {
    }

    #[Given('I open :path from my own context')]
    public function open(string $path): void
    {
        $browser = $this->browser();
        $browser->visit($path);
    }

    #[Given('I open :path with Playwright from my own context')]
    public function openWithPlaywright(string $path): void
    {
        $browser = $this->playwrightBrowser();
        $browser->visit($path);
    }

    #[Then('the :type browser is used')]
    public function assertBrowserType(string $type, bool $configured = false): void
    {
        [$expectedDriver, $classVariable, $defaultClass] = match ($type) {
            'kernel' => [KernelDriver::class, 'KERNEL_BROWSER_CLASS', KernelBrowser::class],
            'playwright' => [PlaywrightDriver::class, 'PLAYWRIGHT_BROWSER_CLASS', PlaywrightBrowser::class],
        };

        $session = $this->getSession();
        $driver = $session->getDriver();
        Assert::true($driver instanceof $expectedDriver, 'Unexpected Mink driver.');
        $browser = match ($type) {
            'kernel' => $this->browser(),
            'playwright' => $this->playwrightBrowser(),
        };
        Assert::true($browser === $driver->browser(), 'The context must use the active session browser.');
        Assert::true('behat' === $this->kernel->getEnvironment(), 'The context kernel was not injected.');
        $expectedClass = $configured ? $defaultClass : ($_SERVER[$classVariable] ?? $defaultClass);
        Assert::true($browser::class === $expectedClass, 'Expected browser class "{expected}", got "{actual}".', [
            'expected' => $expectedClass,
            'actual' => $browser::class,
        ]);
    }

    #[Then('the configured :type browser is used')]
    public function assertConfiguredBrowserType(string $type): void
    {
        $this->assertBrowserType($type, true);
    }

    #[Then('waiting for missing content times out')]
    public function assertWaitTimeout(): void
    {
        $session = $this->getSession();

        Assert::false($session->wait(20, 'false'), 'The condition should time out.');
    }

    #[Then('the browser has no test cookie')]
    public function assertNoCookie(): void
    {
        $session = $this->getSession();

        Assert::true(null === $session->getCookie('behat'), 'A cookie leaked from another scenario.');
    }

    #[When('I set a test cookie')]
    public function setCookie(): void
    {
        $session = $this->getSession();
        $session->setCookie('behat', 'value');

        Assert::true('value' === $session->getCookie('behat'), 'The cookie was not set.');
    }
}

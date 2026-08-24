<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Test;

use PHPUnit\Framework\Attributes\After;
use Playwright\Symfony\Client\BrowserRegistry;
use Playwright\Symfony\Client\BrowserSessionInterface;
use Playwright\Symfony\Client\Interception\AssetServer;
use Playwright\Symfony\Client\PlaywrightKernelClient;
use Playwright\Symfony\Client\RequestConverter;
use Playwright\Symfony\Client\ResponseConverter;
use Playwright\Symfony\Test\PlaywrightTestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Panther\Client as PantherClient;
use Symfony\Component\Panther\PantherTestCase;
use Symfony\Component\Panther\PantherTestCaseTrait;
use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Browser\PantherBrowser;
use Zenstruck\Browser\PlaywrightBrowser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait HasBrowser
{
    private static ?PantherClient $primaryPantherClient = null;

    /**
     * Reused across tests: a fresh session per test gives the isolation, without paying to
     * relaunch the browser each time.
     */
    private static ?BrowserRegistry $sharedPlaywrightBrowser = null;

    /**
     * The sessions claimed by the running test, each an isolated context on the shared browser.
     *
     * @var BrowserSessionInterface[]
     */
    private static array $playwrightSessions = [];

    private static ?KernelInterface $playwrightKernel = null;

    /**
     * @internal
     *
     * @after
     */
    #[After]
    final public static function _resetBrowserClients(): void
    {
        self::$primaryPantherClient = null;

        self::closePlaywrightSessions();

        self::$playwrightKernel = null;
    }

    /**
     * @see PantherTestCase::createPantherClient()
     *
     * @deprecated since 1.11, use {@see self::playwrightBrowser()} instead
     */
    protected function pantherBrowser(array $options = [], array $kernelOptions = [], array $managerOptions = []): PantherBrowser
    {
        if (!\class_exists(PantherClient::class)) {
            throw new \LogicException('symfony/panther must be installed to use the PantherBrowser (composer require symfony/panther).');
        }

        if (!\method_exists(static::class, 'createPantherClient')) {
            throw new \LogicException(\sprintf('A PantherBrowser can only be created in TestCases that extend "%s" or use "%s".', PantherTestCase::class, PantherTestCaseTrait::class));
        }

        trigger_deprecation('zenstruck/browser', '1.11', 'The PantherBrowser is deprecated, use the PlaywrightBrowser instead.');

        $class = $_SERVER['PANTHER_BROWSER_CLASS'] ?? PantherBrowser::class;

        if (!\is_a($class, PantherBrowser::class, true)) {
            throw new \LogicException(\sprintf('"PANTHER_BROWSER_CLASS" env variable must reference a class that extends %s.', PantherBrowser::class));
        }

        $browserOptions = [
            'source_dir' => $_SERVER['BROWSER_SOURCE_DIR'] ?? './var/browser/source',
            'source_debug' => $_SERVER['BROWSER_SOURCE_DEBUG'] ?? false,
            'screenshot_dir' => $_SERVER['BROWSER_SCREENSHOT_DIR'] ?? './var/browser/screenshots',
            'console_log_dir' => $_SERVER['BROWSER_CONSOLE_LOG_DIR'] ?? './var/browser/console-logs',
        ];

        if ($_SERVER['BROWSER_ALWAYS_START_WEBSERVER'] ?? null) {
            $_SERVER['PANTHER_APP_ENV'] = $_SERVER['APP_ENV'] ?? 'test'; // use current environment
            $_SERVER['SYMFONY_PROJECT_DEFAULT_ROUTE_URL'] = ''; // ignore existing server running with Symfony CLI
        }

        if (self::$primaryPantherClient) {
            $browser = new $class(static::createAdditionalPantherClient(), $browserOptions); // @phpstan-ignore staticMethod.notFound
        } else {
            self::$primaryPantherClient = static::createPantherClient(
                \array_merge(['browser' => $_SERVER['PANTHER_BROWSER'] ?? PantherTestCase::CHROME], $options),
                $kernelOptions,
                $managerOptions,
            );

            $browser = new $class(self::$primaryPantherClient, $browserOptions);
        }

        BrowserExtension::registerBrowser($browser);

        return $browser;
    }

    /**
     * @see WebTestCase::createClient()
     */
    protected function browser(array $options = [], array $server = []): KernelBrowser
    {
        if (!$this instanceof KernelTestCase) {
            throw new \LogicException(\sprintf('A KernelBrowser can only be created in TestCases that extend "%s".', KernelTestCase::class));
        }

        $class = $_SERVER['KERNEL_BROWSER_CLASS'] ?? KernelBrowser::class;

        if (!\is_a($class, KernelBrowser::class, true)) {
            throw new \LogicException(\sprintf('"KERNEL_BROWSER_CLASS" env variable must reference a class that extends %s.', KernelBrowser::class));
        }

        $browserOptions = [
            'source_dir' => $_SERVER['BROWSER_SOURCE_DIR'] ?? './var/browser/source',
            'source_debug' => $_SERVER['BROWSER_SOURCE_DEBUG'] ?? false,
            'follow_redirects' => (bool) ($_SERVER['BROWSER_FOLLOW_REDIRECTS'] ?? true),
            'catch_exceptions' => (bool) ($_SERVER['BROWSER_CATCH_EXCEPTIONS'] ?? true),
        ];

        if ($this instanceof WebTestCase) {
            static::ensureKernelShutdown();

            $browser = new $class(static::createClient($options, $server), $browserOptions); // @phpstan-ignore staticMethod.notFound
        } else {
            // reboot kernel before starting browser
            static::bootKernel($options);

            if (!static::getContainer()->has('test.client')) {
                throw new \RuntimeException('The Symfony test client is not enabled.');
            }

            $client = static::getContainer()->get('test.client');
            $client->setServerParameters($server);

            $browser = new $class($client, $browserOptions);
        }

        BrowserExtension::registerBrowser($browser);

        return $browser;
    }

    protected function playwrightBrowser(): PlaywrightBrowser
    {
        if (!\class_exists(PlaywrightKernelClient::class)) {
            throw new \LogicException('playwright-php/playwright-symfony must be installed to use the PlaywrightBrowser (composer require --dev playwright-php/playwright-symfony).');
        }

        if ($this instanceof PlaywrightTestCase) {
            throw new \LogicException(\sprintf('A PlaywrightBrowser cannot be created in a TestCase that extends "%s": it manages its own browser and sessions, which would conflict with the ones managed here.', PlaywrightTestCase::class));
        }

        if (!$this instanceof KernelTestCase) {
            throw new \LogicException(\sprintf('A PlaywrightBrowser can only be created in TestCases that extend "%s".', KernelTestCase::class));
        }

        $class = $_SERVER['PLAYWRIGHT_BROWSER_CLASS'] ?? PlaywrightBrowser::class;

        if (!\is_a($class, PlaywrightBrowser::class, true)) {
            throw new \LogicException(\sprintf('"PLAYWRIGHT_BROWSER_CLASS" env variable must reference a class that extends %s.', PlaywrightBrowser::class));
        }

        $client = $this->playwrightClient();

        $browser = new $class($client, [
            'source_dir' => $_SERVER['BROWSER_SOURCE_DIR'] ?? './var/browser/source',
            'source_debug' => $_SERVER['BROWSER_SOURCE_DEBUG'] ?? false,
            'screenshot_dir' => $_SERVER['BROWSER_SCREENSHOT_DIR'] ?? './var/browser/screenshots',
            'console_log_dir' => $_SERVER['BROWSER_CONSOLE_LOG_DIR'] ?? './var/browser/console-logs',
            'follow_redirects' => (bool) ($_SERVER['BROWSER_FOLLOW_REDIRECTS'] ?? true),
            'catch_exceptions' => (bool) ($_SERVER['BROWSER_CATCH_EXCEPTIONS'] ?? true),
        ]);

        BrowserExtension::registerBrowser($browser);

        return $browser;
    }

    private function playwrightClient(): PlaywrightKernelClient
    {
        $session = self::claimPlaywrightSession();

        self::$playwrightKernel ??= static::bootKernel();

        // the bundle is optional: its configuration is used when available. A disabled bundle
        // registers no services or parameters at all, so this covers both
        $container = self::$playwrightKernel->getContainer();
        $hosts = $container->hasParameter('playwright.intercepted_hosts') ? $container->getParameter('playwright.intercepted_hosts') : null;

        // the configured default is an env placeholder, so it is null unless PLAYWRIGHT_BASE_URL is set
        $baseUrl = $container->hasParameter('playwright.base_url') ? $container->getParameter('playwright.base_url') : null;

        return new PlaywrightKernelClient(
            $session,
            self::$playwrightKernel,
            new RequestConverter(),
            new ResponseConverter(),
            [],
            \is_array($hosts) ? $hosts : null,
            null,
            $container->has(AssetServer::class) ? $container->get(AssetServer::class) : null,
            \is_string($baseUrl) && '' !== $baseUrl ? $baseUrl : 'http://localhost',
        );
    }

    private static function claimPlaywrightSession(): BrowserSessionInterface
    {
        $requested = BrowserRegistry::fromEnvironment();

        // the environment can differ between test classes, so the shared browser may not match
        if (self::$sharedPlaywrightBrowser && !self::$sharedPlaywrightBrowser->equals($requested)) {
            self::stopSharedPlaywrightBrowser();
        }

        $browser = self::$sharedPlaywrightBrowser ??= $requested;

        return self::$playwrightSessions[] = self::createPlaywrightSession($browser);
    }

    private static function createPlaywrightSession(BrowserRegistry $browser): BrowserSessionInterface
    {
        try {
            return $browser->createSession();
        } catch (\Throwable) {
            // an exception thrown by the app poisons the connection: playwright-php re-throws it on
            // every subsequent call, so the browser has to be replaced rather than reused
            self::stopSharedPlaywrightBrowser();
        }

        $replacement = self::$sharedPlaywrightBrowser = BrowserRegistry::fromEnvironment();

        return $replacement->createSession();
    }

    private static function closePlaywrightSessions(): void
    {
        $sessions = self::$playwrightSessions;
        self::$playwrightSessions = [];

        foreach ($sessions as $session) {
            // closing is what surfaces a poisoned connection, so a failure replaces the browser
            // instead of leaving the next test to inherit it
            if (!self::closePlaywrightSession($session)) {
                return;
            }
        }
    }

    private static function closePlaywrightSession(BrowserSessionInterface $session): bool
    {
        try {
            self::$sharedPlaywrightBrowser?->closeSession($session);
        } catch (\Throwable) {
            self::stopSharedPlaywrightBrowser();

            return false;
        }

        return true;
    }

    private static function stopSharedPlaywrightBrowser(): void
    {
        self::$playwrightSessions = [];
        self::$sharedPlaywrightBrowser?->stop();
        self::$sharedPlaywrightBrowser = null;
    }
}

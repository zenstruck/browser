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
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Panther\Client as PantherClient;
use Symfony\Component\Panther\PantherTestCase;
use Symfony\Component\Panther\PantherTestCaseTrait;
use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Browser\PantherBrowser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait HasBrowser
{
    private static ?PantherClient $primaryPantherClient = null;

    /**
     * @internal
     *
     * @after
     */
    #[After]
    final public static function _resetBrowserClients(): void
    {
        self::$primaryPantherClient = null;
    }

    /**
     * @see PantherTestCase::createPantherClient()
     */
    protected function pantherBrowser(array $options = [], array $kernelOptions = [], array $managerOptions = []): PantherBrowser
    {
        if (!\class_exists(PantherClient::class)) {
            throw new \LogicException('symfony/panther must be installed to use the PantherBrowser (composer require symfony/panther).');
        }

        if (!\method_exists(static::class, 'createPantherClient')) {
            throw new \LogicException(\sprintf('A PantherBrowser can only be created in TestCases that extend "%s" or use "%s".', PantherTestCase::class, PantherTestCaseTrait::class));
        }

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
            $browser = new $class(static::createAdditionalPantherClient(), $browserOptions);
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

            $browser = new $class(static::createClient($options, $server), $browserOptions);
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
}

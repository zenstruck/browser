<?php

namespace Zenstruck\Browser\Test;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Panther\Client as PantherClient;
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
     * @after
     */
    final public static function _resetBrowserClients(): void
    {
        self::$primaryPantherClient = null;
    }

    protected function pantherBrowser(array $options = [], array $kernelOptions = [], array $managerOptions = []): PantherBrowser
    {
        $class = $_SERVER['PANTHER_BROWSER_CLASS'] ?? PantherBrowser::class;

        if (!\is_a($class, PantherBrowser::class, true)) {
            throw new \RuntimeException(\sprintf('"PANTHER_BROWSER_CLASS" env variable must reference a class that extends %s.', PantherBrowser::class));
        }

        if (self::$primaryPantherClient) {
            $browser = new $class(static::createAdditionalPantherClient());
        } else {
            self::$primaryPantherClient = static::createPantherClient(
                \array_merge(['browser' => $_SERVER['PANTHER_BROWSER'] ?? static::CHROME], $options),
                $kernelOptions,
                $managerOptions
            );

            $browser = new $class(self::$primaryPantherClient);
        }

        BrowserExtension::registerBrowser($browser);

        return $browser
            ->setSourceDir($_SERVER['BROWSER_SOURCE_DIR'] ?? './var/browser/source')
            ->setScreenshotDir($_SERVER['BROWSER_SCREENSHOT_DIR'] ?? './var/browser/screenshots')
            ->setConsoleLogDir($_SERVER['BROWSER_CONSOLE_LOG_DIR'] ?? './var/browser/console-logs')
        ;
    }

    protected function browser(array $kernelOptions = [], array $server = []): KernelBrowser
    {
        if (!$this instanceof KernelTestCase) {
            throw new \RuntimeException(\sprintf('The "%s" method can only be used on TestCases that extend "%s".', __METHOD__, KernelTestCase::class));
        }

        $class = $_SERVER['KERNEL_BROWSER_CLASS'] ?? KernelBrowser::class;

        if (!\is_a($class, KernelBrowser::class, true)) {
            throw new \RuntimeException(\sprintf('"KERNEL_BROWSER_CLASS" env variable must reference a class that extends %s.', KernelBrowser::class));
        }

        if ($this instanceof WebTestCase) {
            static::ensureKernelShutdown();

            $browser = new $class(static::createClient($kernelOptions, $server));
        } else {
            // reboot kernel before starting browser
            static::bootKernel($kernelOptions);

            if (!static::getContainer()->has('test.client')) {
                throw new \RuntimeException('The Symfony test client is not enabled.');
            }

            $client = static::getContainer()->get('test.client');
            $client->setServerParameters($server);

            $browser = new $class($client);
        }

        BrowserExtension::registerBrowser($browser);

        return $browser
            ->setSourceDir($_SERVER['BROWSER_SOURCE_DIR'] ?? './var/browser/source')
        ;
    }
}

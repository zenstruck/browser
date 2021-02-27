<?php

namespace Zenstruck\Browser\Test;

use Symfony\Component\Panther\Client as PantherClient;
use Symfony\Component\Panther\PantherTestCase;
use Zenstruck\Browser\PantherBrowser;

trait HasPantherBrowser
{
    private static ?PantherClient $primaryPantherClient = null;

    /**
     * @internal
     * @after
     */
    final public static function _resetPantherBrowserClient(): void
    {
        self::$primaryPantherClient = null;
    }

    protected function pantherBrowser(array $options = [], array $kernelOptions = [], array $managerOptions = []): PantherBrowser
    {
        $class = $_SERVER['PANTHER_BROWSER_CLASS'] ?? PantherBrowser::class;

        if (!$this instanceof PantherTestCase) {
            throw new \RuntimeException(\sprintf('The "%s" method can only be used on TestCases that extend "%s".', __METHOD__, PantherTestCase::class));
        }

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
}

<?php

namespace Zenstruck\Browser\Test;

use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\PantherTestCase;
use Zenstruck\Browser\PantherBrowser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental in 1.0
 *
 * @method PantherBrowser browser()
 */
trait HasPantherBrowser
{
    use HasBrowser;

    private static ?Client $primaryPantherClient = null;

    /**
     * @internal
     * @after
     */
    public static function _resetPrimaryPantherClient(): void
    {
        self::$primaryPantherClient = null;
    }

    protected function createBrowser(): PantherBrowser
    {
        if (!$this instanceof PantherTestCase) {
            throw new \RuntimeException(\sprintf('The "%s" trait can only be used on TestCases that extend "%s".', __TRAIT__, PantherTestCase::class));
        }

        $class = static::pantherBrowserClass();

        if (!\is_a($class, PantherBrowser::class, true)) {
            throw new \RuntimeException(\sprintf('"PANTHER_BROWSER_CLASS" env variable must reference a class that extends %s.', PantherBrowser::class));
        }

        if (self::$primaryPantherClient) {
            return new $class(static::createAdditionalPantherClient());
        }

        return new $class(self::$primaryPantherClient = static::createPantherClient(['browser' => $_SERVER['PANTHER_BROWSER'] ?? static::CHROME]));
    }

    protected static function pantherBrowserClass(): string
    {
        return $_SERVER['PANTHER_BROWSER_CLASS'] ?? PantherBrowser::class;
    }
}

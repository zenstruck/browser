<?php

namespace Zenstruck\Browser\Test;

use Symfony\Component\Panther\PantherTestCase;
use Zenstruck\Browser\HttpBrowser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @method HttpBrowser browser()
 */
trait HasHttpBrowser
{
    use HasBrowser;

    protected function createBrowser(): HttpBrowser
    {
        if (!$this instanceof PantherTestCase) {
            throw new \RuntimeException(\sprintf('The "%s" trait can only be used on TestCases that extend "%s".', __TRAIT__, PantherTestCase::class));
        }

        $class = static::httpBrowserClass();

        if (!\is_a($class, HttpBrowser::class, true)) {
            throw new \RuntimeException(\sprintf('"HTTP_BROWSER_CLASS" env variable must reference a class that extends %s.', HttpBrowser::class));
        }

        return new $class(static::createHttpBrowserClient());
    }

    protected static function httpBrowserClass(): string
    {
        return $_SERVER['HTTP_BROWSER_CLASS'] ?? HttpBrowser::class;
    }
}

<?php

namespace Zenstruck\Browser\Test;

use PHPUnit\Runner\AfterTestErrorHook;
use PHPUnit\Runner\AfterTestFailureHook;
use PHPUnit\Runner\AfterTestHook;
use PHPUnit\Runner\BeforeFirstTestHook;
use PHPUnit\Runner\BeforeTestHook;
use Zenstruck\Browser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class BrowserExtension implements BeforeFirstTestHook, BeforeTestHook, AfterTestHook, AfterTestErrorHook, AfterTestFailureHook
{
    /** @var Browser[] */
    private static array $registeredBrowsers = [];
    private static bool $enabled = false;

    /**
     * @internal
     */
    public static function registerBrowser(Browser $browser): void
    {
        if (!self::$enabled) {
            return;
        }

        self::$registeredBrowsers[] = $browser;
    }

    public function executeBeforeFirstTest(): void
    {
        self::$enabled = true;
    }

    public function executeBeforeTest(string $test): void
    {
        self::reset();
    }

    public function executeAfterTest(string $test, float $time): void
    {
        self::reset();
    }

    public function executeAfterTestError(string $test, string $message, float $time): void
    {
        self::dumpBrowsers($test, 'error');
    }

    public function executeAfterTestFailure(string $test, string $message, float $time): void
    {
        self::dumpBrowsers($test, 'failure');
    }

    private static function dumpBrowsers(string $test, string $type): void
    {
        if (empty(self::$registeredBrowsers)) {
            return;
        }

        $filename = \sprintf('%s_%s', $type, self::normalizeTestName($test));

        foreach (self::$registeredBrowsers as $i => $browser) {
            try {
                $browser->dumpCurrentState("{$filename}__{$i}");
            } catch (\Throwable $e) {
                // noop - swallow exceptions related to dumping the current state so as to not
                // lose the actual error/failure.
            }
        }
    }

    private static function normalizeTestName(string $name): string
    {
        \preg_match('#^([\w:\\\]+)(.+\#(\d+).+)?$#', $name, $matches);

        $normalized = \strtr($matches[1], '\\:', '-_');

        if (isset($matches[3])) {
            $normalized .= '__data-set-'.$matches[3];
        }

        return $normalized;
    }

    private static function reset(): void
    {
        self::$registeredBrowsers = [];
    }
}

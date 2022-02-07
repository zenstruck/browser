<?php

namespace Zenstruck\Browser\Test;

use PHPUnit\Runner\AfterLastTestHook;
use PHPUnit\Runner\AfterTestErrorHook;
use PHPUnit\Runner\AfterTestFailureHook;
use PHPUnit\Runner\AfterTestHook;
use PHPUnit\Runner\BeforeFirstTestHook;
use PHPUnit\Runner\BeforeTestHook;
use Zenstruck\Browser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class BrowserExtension implements BeforeFirstTestHook, BeforeTestHook, AfterTestHook, AfterTestErrorHook, AfterTestFailureHook, AfterLastTestHook
{
    /** @var Browser[] */
    private static array $registeredBrowsers = [];
    private static bool $enabled = false;
    private array $savedArtifacts = [];

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
        foreach (self::$registeredBrowsers as $browser) {
            foreach ($browser->savedArtifacts() as $category => $artifacts) {
                if (!\count($artifacts)) {
                    continue;
                }

                $this->savedArtifacts[$test][$category] = $artifacts;
            }
        }

        self::reset();
    }

    public function executeAfterLastTest(): void
    {
        if (empty($this->savedArtifacts)) {
            return;
        }

        echo "\n\nSaved Browser Artifacts:";

        foreach ($this->savedArtifacts as $test => $categories) {
            echo "\n\n  {$test}";

            foreach ($categories as $category => $artifacts) {
                echo "\n    {$category}:";

                foreach ($artifacts as $artifact) {
                    echo "\n      * {$artifact}:";
                }
            }
        }
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
        // Try to match for a numeric data set index. If it didn't, match for a string one.
        if (!\preg_match('#^([\w:\\\]+)(.+\#(\d+).+)?$#', $name, $matches)) {
            \preg_match('#^([\w:\\\]+)(.+"([\w ]+)".+)?$#', $name, $matches);
        }

        $normalized = \strtr($matches[1], '\\:', '-_');

        if (isset($matches[3])) {
            $normalized .= '__data-set-'.strtr($matches[3], '\\: ', '-_-');
        }

        return $normalized;
    }

    private static function reset(): void
    {
        self::$registeredBrowsers = [];
    }
}

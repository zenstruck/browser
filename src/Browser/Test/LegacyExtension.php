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

use Zenstruck\Browser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class LegacyExtension
{
    /** @var Browser[] */
    private static array $registeredBrowsers = [];
    private static bool $enabled = false;

    /** @var array<string,array<string,string[]>> */
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
        self::saveBrowserStates($test, 'error');
    }

    public function executeAfterTestFailure(string $test, string $message, float $time): void
    {
        self::saveBrowserStates($test, 'failure');
    }

    private static function saveBrowserStates(string $test, string $type): void
    {
        if (empty(self::$registeredBrowsers)) {
            return;
        }

        $filename = \sprintf('%s_%s', $type, self::normalizeTestName($test));

        foreach (self::$registeredBrowsers as $i => $browser) {
            try {
                $browser->saveCurrentState("{$filename}__{$i}");
            } catch (\Throwable $e) {
                // noop - swallow exceptions related to dumping the current state so as to not
                // lose the actual error/failure.
            }
        }
    }

    private static function normalizeTestName(string $name): string
    {
        if (!\mb_strstr($name, 'with data set')) {
            return \strtr($name, '\\:', '-_');
        }

        // Try to match for a numeric data set index. If it didn't, match for a string one.
        if (!\preg_match('#^(?<test>[\w:\\\]+) with data set \#(?<dataset>\d+)#', $name, $matches)) {
            \preg_match('#^(?<test>[\w:\\\]+) with data set "(?<dataset>.*)"#', $name, $matches);
        }

        $normalized = \strtr($matches['test'], '\\:', '-_'); // @phpstan-ignore-line

        if (isset($matches['dataset'])) {
            $normalized .= '__data-set-'.\preg_replace('/\W+/', '-', $matches['dataset']);
        }

        return $normalized;
    }

    private static function reset(): void
    {
        self::$registeredBrowsers = [];
    }
}

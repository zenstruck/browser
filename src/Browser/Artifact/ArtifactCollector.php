<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Artifact;

use Zenstruck\Browser\BrowserRegistry;

/**
 * Drives the test-or-scenario lifecycle that captures browser state on failure
 * and accumulates the "Saved Browser Artifacts" summary printed at end of suite.
 * Test-runner-agnostic: PHPUnit and Behat each wire their own events to these
 * methods.
 *
 * @author Hugo Hamon <hello@kodero.fr>
 */
final class ArtifactCollector
{
    /** @var array<string, array<string, string[]>> */
    private array $savedArtifacts = [];

    public function __construct(
        private readonly BrowserRegistry $registry,
        private readonly ArtifactSink $sink,
    ) {
    }

    public function onSuiteStart(): void
    {
        $this->registry->start();
    }

    public function onScenarioStart(string $name): void
    {
        $this->registry->clear();
    }

    public function onScenarioFailed(string $name, FailureType $type): void
    {
        if ($this->registry->isEmpty()) {
            return;
        }

        $filename = \sprintf('%s_%s', $type->value, self::normalizeName($name));

        foreach ($this->registry->all() as $i => $browser) {
            try {
                $browser->saveCurrentState("{$filename}__{$i}");
            } catch (\Throwable) {
                // swallow exceptions related to dumping the current state so as to not
                // lose the actual error/failure being reported by the test runner
            }
        }
    }

    public function onScenarioFinish(string $name): void
    {
        foreach ($this->registry->all() as $browser) {
            foreach ($browser->savedArtifacts() as $category => $artifacts) {
                if (\count($artifacts) === 0) {
                    continue;
                }

                $this->savedArtifacts[$name][$category] = $artifacts;
            }
        }

        $this->registry->clear();
    }

    public function onSuiteFinish(): void
    {
        $this->sink->writeSummary($this->savedArtifacts);
        $this->registry->stop();
    }

    private static function normalizeName(string $name): string
    {
        if (!\mb_strstr($name, 'with data set')) {
            return \strtr($name, '\\:', '-_');
        }

        // Try to match for a numeric data set index. If it didn't, match for a string one.
        if (!\preg_match('#^(?<test>[\w:\\\]+) with data set \#(?<dataset>\d+)#', $name, $matches)) {
            \preg_match('#^(?<test>[\w:\\\]+) with data set "(?<dataset>.*)"#', $name, $matches);
        }

        $normalized = \strtr($matches['test'], '\\:', '-_');

        if (isset($matches['dataset'])) {
            $normalized .= '__data-set-'.\preg_replace('/\W+/', '-', $matches['dataset']);
        }

        return $normalized;
    }
}

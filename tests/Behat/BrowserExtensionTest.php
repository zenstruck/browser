<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Tests\Behat;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * @group playwright
 */
#[Group('playwright')]
final class BrowserExtensionTest extends TestCase
{
    /**
     * @test
     */
    #[Test]
    public function runs_features_with_both_browsers(): void
    {
        $process = $this->behat();

        self::assertSame(0, $process->getExitCode(), $process->getOutput().$process->getErrorOutput());
        self::assertStringContainsString('189 scenarios (189 passed)', $process->getOutput());
    }

    /**
     * @test
     */
    #[Test]
    public function cleans_up_after_failed_scenarios(): void
    {
        $process = $this->behat('failure');

        self::assertSame(1, $process->getExitCode(), $process->getOutput().$process->getErrorOutput());
        self::assertStringContainsString('2 scenarios (1 passed, 1 failed)', $process->getOutput());
        self::assertStringContainsString('The text "This text does not exist" was not found anywhere in the text of the current page.', $process->getOutput());
    }

    private function behat(string $profile = 'default'): Process
    {
        $process = new Process([
            \PHP_BINARY,
            'vendor/bin/behat',
            '--config=tests/Behat/behat.php',
            '--profile='.$profile,
            '--format=progress',
            '--no-colors',
            '--strict',
        ], \dirname(__DIR__, 2));
        $process->setTimeout(180);
        $process->run();

        return $process;
    }
}

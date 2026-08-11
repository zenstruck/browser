<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Tests;

use PHPUnit\Framework\TestCase;
use Zenstruck\Browser\Artifact\ArtifactSink;
use Zenstruck\Browser\Bridge\Behat\Output\BehatOutputArtifactSink;

/**
 * @author Hugo Hamon <hello@kodero.fr>
 */
final class BehatOutputArtifactSinkTest extends TestCase
{
    /**
     * @test
     */
    public function implements_artifact_sink(): void
    {
        $this->assertInstanceOf(ArtifactSink::class, new BehatOutputArtifactSink());
    }

    /**
     * @test
     */
    public function writes_nothing_when_empty(): void
    {
        (new BehatOutputArtifactSink())->writeSummary([]);

        $this->expectNotToPerformAssertions();
    }

    /**
     * @test
     */
    public function writes_summary_to_stdout(): void
    {
        $output = $this->captureStdout(static fn (): array => [
            'Test::case' => ['html' => ['/tmp/page.html']],
        ]);

        $this->assertStringContainsString('Saved Browser Artifacts', $output);
        $this->assertStringContainsString('Test::case', $output);
        $this->assertStringContainsString('html:', $output);
        $this->assertStringContainsString('/tmp/page.html:', $output);
    }

    /**
     * @test
     */
    public function writes_summary_with_multiple_tests(): void
    {
        $output = $this->captureStdout(static fn (): array => [
            'First::a' => ['html' => ['/tmp/a.html']],
            'Second::b' => ['html' => ['/tmp/b.html']],
        ]);

        $this->assertStringContainsString('First::a', $output);
        $this->assertStringContainsString('Second::b', $output);
        $this->assertStringContainsString('/tmp/a.html:', $output);
        $this->assertStringContainsString('/tmp/b.html:', $output);
    }

    /**
     * @test
     */
    public function writes_summary_with_multiple_categories(): void
    {
        $output = $this->captureStdout(static fn (): array => [
            'Test::case' => [
                'html' => ['/tmp/page.html'],
                'pdf' => ['/tmp/report.pdf'],
            ],
        ]);

        $this->assertStringContainsString('html:', $output);
        $this->assertStringContainsString('pdf:', $output);
        $this->assertStringContainsString('/tmp/page.html:', $output);
        $this->assertStringContainsString('/tmp/report.pdf:', $output);
    }

    /**
     * @test
     */
    public function writes_summary_with_multiple_artifacts(): void
    {
        $output = $this->captureStdout(static fn (): array => [
            'Test::case' => ['html' => ['/tmp/a.html', '/tmp/b.html']],
        ]);

        $this->assertStringContainsString('/tmp/a.html:', $output);
        $this->assertStringContainsString('/tmp/b.html:', $output);
    }

    /**
     * @test
     */
    public function summary_format_is_correct(): void
    {
        $output = $this->captureStdout(static fn (): array => [
            'Test::case' => ['html' => ['/tmp/page.html']],
        ]);

        $expected = "\n\nSaved Browser Artifacts:\n\n  Test::case\n    html:\n      * /tmp/page.html:\n";
        $this->assertSame($expected, $output);
    }

    /**
     * @param callable(): array $inputProvider
     */
    private function captureStdout(callable $inputProvider): string
    {
        $tmpFile = \tempnam(\sys_get_temp_dir(), 'browser_stdout_');

        $phpCode = \sprintf(
            '<?php require %s; $s = new \%s(); $s->writeSummary(%s);',
            \var_export(\dirname(__DIR__).'/vendor/autoload.php', true),
            BehatOutputArtifactSink::class,
            \var_export($inputProvider(), true),
        );

        \file_put_contents($tmpFile, $phpCode);

        $output = \shell_exec(\sprintf('php %s 2>&1', \escapeshellarg($tmpFile)));

        \unlink($tmpFile);

        if ($output === null) {
            $this->markTestSkipped('shell_exec or php binary not available.');
        }

        return $output;
    }
}

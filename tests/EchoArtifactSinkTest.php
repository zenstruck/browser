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
use Zenstruck\Browser\Artifact\EchoArtifactSink;

/**
 * @author Hugo Hamon <hello@kodero.fr>
 */
final class EchoArtifactSinkTest extends TestCase
{
    /**
     * @test
     */
    public function implements_artifact_sink(): void
    {
        $this->assertInstanceOf(ArtifactSink::class, new EchoArtifactSink());
    }

    /**
     * @test
     */
    public function writes_nothing_when_no_artifacts_saved(): void
    {
        ob_start();
        (new EchoArtifactSink())->writeSummary([]);
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }

    /**
     * @test
     */
    public function writes_summary_with_single_test_single_category_single_artifact(): void
    {
        ob_start();
        (new EchoArtifactSink())->writeSummary([
            'MyTest::test_method' => [
                'html' => ['/tmp/test.html'],
            ],
        ]);
        $output = ob_get_clean();

        $this->assertStringContainsString('Saved Browser Artifacts', $output);
        $this->assertStringContainsString('MyTest::test_method', $output);
        $this->assertStringContainsString('html:', $output);
        $this->assertStringContainsString('/tmp/test.html:', $output);
    }

    /**
     * @test
     */
    public function writes_summary_with_multiple_categories(): void
    {
        ob_start();
        (new EchoArtifactSink())->writeSummary([
            'MyTest::test_method' => [
                'html' => ['/tmp/test.html'],
                'pdf' => ['/tmp/report.pdf'],
            ],
        ]);
        $output = ob_get_clean();

        $this->assertStringContainsString('html:', $output);
        $this->assertStringContainsString('pdf:', $output);
        $this->assertStringContainsString('/tmp/test.html:', $output);
        $this->assertStringContainsString('/tmp/report.pdf:', $output);
    }

    /**
     * @test
     */
    public function writes_summary_with_multiple_artifacts_per_category(): void
    {
        ob_start();
        (new EchoArtifactSink())->writeSummary([
            'MyTest::test_method' => [
                'html' => ['/tmp/test.html', '/tmp/test2.html'],
            ],
        ]);
        $output = ob_get_clean();

        $this->assertStringContainsString('/tmp/test.html:', $output);
        $this->assertStringContainsString('/tmp/test2.html:', $output);
    }

    /**
     * @test
     */
    public function writes_summary_with_multiple_tests(): void
    {
        ob_start();
        (new EchoArtifactSink())->writeSummary([
            'FirstTest::test_a' => [
                'html' => ['/tmp/a.html'],
            ],
            'SecondTest::test_b' => [
                'html' => ['/tmp/b.html'],
            ],
        ]);
        $output = ob_get_clean();

        $this->assertStringContainsString('FirstTest::test_a', $output);
        $this->assertStringContainsString('SecondTest::test_b', $output);
    }

    /**
     * @test
     */
    public function writes_summary_with_empty_category_array(): void
    {
        ob_start();
        (new EchoArtifactSink())->writeSummary([
            'MyTest::test_method' => [
                'html' => [],
            ],
        ]);
        $output = ob_get_clean();

        $this->assertStringContainsString('Saved Browser Artifacts', $output);
        $this->assertStringContainsString('html:', $output);
    }

    /**
     * @test
     */
    public function summary_formatting_is_correct(): void
    {
        ob_start();
        (new EchoArtifactSink())->writeSummary([
            'Test::case' => [
                'html' => ['/tmp/page.html'],
            ],
        ]);
        $output = ob_get_clean();

        $expected = "\n\nSaved Browser Artifacts:\n\n  Test::case\n    html:\n      * /tmp/page.html:";
        $this->assertSame($expected, $output);
    }
}

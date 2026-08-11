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
use Zenstruck\Browser\Artifact\ArtifactCollector;
use Zenstruck\Browser\Artifact\EchoArtifactSink;
use Zenstruck\Browser\Artifact\FailureType;
use Zenstruck\Browser\BrowserRegistry;

/**
 * Thin PHPUnit (<10) adapter forwarding lifecycle events to the framework-agnostic
 * {@see ArtifactCollector}. Also forwards the PHPUnit-10 subscribers wired in
 * {@see BootstrappedExtension}.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class LegacyExtension
{
    private readonly ArtifactCollector $collector;

    public function __construct()
    {
        $this->collector = new ArtifactCollector(BrowserRegistry::default(), new EchoArtifactSink());
    }

    /**
     * @internal
     *
     * @deprecated since 1.10, use {@see BrowserRegistry::default()}->register() instead.
     */
    public static function registerBrowser(Browser $browser): void
    {
        BrowserRegistry::default()->register($browser);
    }

    public function executeBeforeFirstTest(): void
    {
        $this->collector->onSuiteStart();
    }

    public function executeBeforeTest(string $test): void
    {
        $this->collector->onScenarioStart($test);
    }

    public function executeAfterTest(string $test, float $time): void
    {
        $this->collector->onScenarioFinish($test);
    }

    public function executeAfterLastTest(): void
    {
        $this->collector->onSuiteFinish();
    }

    public function executeAfterTestError(string $test, string $message, float $time): void
    {
        $this->collector->onScenarioFailed($test, FailureType::Error);
    }

    public function executeAfterTestFailure(string $test, string $message, float $time): void
    {
        $this->collector->onScenarioFailed($test, FailureType::Failure);
    }
}

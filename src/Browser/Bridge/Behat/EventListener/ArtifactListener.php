<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Bridge\Behat\EventListener;

use Behat\Behat\EventDispatcher\Event\AfterScenarioTested;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
use Behat\Testwork\EventDispatcher\Event\AfterSuiteTested;
use Behat\Testwork\EventDispatcher\Event\BeforeSuiteTested;
use Behat\Testwork\Tester\Result\TestResult;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Zenstruck\Browser\Artifact\ArtifactCollector;
use Zenstruck\Browser\Artifact\FailureType;
use Zenstruck\Browser\KernelBooter;

/**
 * Wires Behat's suite + scenario events to {@see ArtifactCollector}: captures
 * browser state on scenario failure and emits the end-of-suite "Saved Browser
 * Artifacts" summary. Also resets the {@see KernelBooter} between scenarios so
 * each one starts with a fresh container.
 *
 * @author Hugo Hamon <hello@kodero.fr>
 */
final class ArtifactListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly ArtifactCollector $collector,
        private readonly KernelBooter $booter,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeSuiteTested::class => ['onBeforeSuite', 0],
            BeforeScenarioTested::class => ['onBeforeScenario', 0],
            AfterScenarioTested::class => ['onAfterScenario', 0],
            AfterSuiteTested::class => ['onAfterSuite', 0],
        ];
    }

    public function onBeforeSuite(BeforeSuiteTested $event): void
    {
        $this->collector->onSuiteStart();
    }

    public function onBeforeScenario(BeforeScenarioTested $event): void
    {
        $this->booter->reset();
        $this->collector->onScenarioStart($this->formatName($event));
    }

    public function onAfterScenario(AfterScenarioTested $event): void
    {
        $name = $this->formatName($event);

        if ($event->getTestResult()->getResultCode() === TestResult::FAILED) {
            $this->collector->onScenarioFailed($name, FailureType::Failure);
        }

        $this->collector->onScenarioFinish($name);
    }

    public function onAfterSuite(AfterSuiteTested $event): void
    {
        $this->collector->onSuiteFinish();
    }

    private function formatName(BeforeScenarioTested|AfterScenarioTested $event): string
    {
        return \sprintf(
            '%s:%d (%s)',
            $event->getFeature()->getFile() ?? 'unknown',
            $event->getScenario()->getLine(),
            $event->getScenario()->getTitle() ?? 'untitled',
        );
    }
}

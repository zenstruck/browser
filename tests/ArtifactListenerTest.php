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

use Behat\Behat\EventDispatcher\Event\AfterScenarioTested;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioLikeInterface;
use Behat\Testwork\EventDispatcher\Event\AfterSuiteTested;
use Behat\Testwork\EventDispatcher\Event\BeforeSuiteTested;
use Behat\Testwork\Environment\Environment;
use Behat\Testwork\Specification\SpecificationIterator;
use Behat\Testwork\Tester\Result\TestResult;
use Behat\Testwork\Tester\Setup\Teardown;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Zenstruck\Browser;
use Zenstruck\Browser\Artifact\ArtifactCollector;
use Zenstruck\Browser\Artifact\ArtifactSink;
use Zenstruck\Browser\Bridge\Behat\EventListener\ArtifactListener;
use Zenstruck\Browser\BrowserRegistry;
use Zenstruck\Browser\KernelBooter;

/**
 * @author Hugo Hamon <hello@kodero.fr>
 */
final class ArtifactListenerTest extends TestCase
{
    private BrowserRegistry $registry;
    private ArtifactSink&MockObject $sink;
    private ArtifactCollector $collector;
    private KernelBooter&MockObject $booter;
    private ArtifactListener $listener;

    protected function setUp(): void
    {
        BrowserRegistry::resetDefault();
        $this->registry = new BrowserRegistry();
        $this->sink = $this->createMock(ArtifactSink::class);
        $this->collector = new ArtifactCollector($this->registry, $this->sink);
        $this->booter = $this->createMock(KernelBooter::class);
        $this->listener = new ArtifactListener($this->collector, $this->booter);
    }

    protected function tearDown(): void
    {
        BrowserRegistry::resetDefault();
    }

    /**
     * @test
     */
    public function getSubscribedEvents_returns_expected_events(): void
    {
        $this->assertSame([
            BeforeSuiteTested::class => ['onBeforeSuite', 0],
            BeforeScenarioTested::class => ['onBeforeScenario', 0],
            AfterScenarioTested::class => ['onAfterScenario', 0],
            AfterSuiteTested::class => ['onAfterSuite', 0],
        ], ArtifactListener::getSubscribedEvents());
    }

    /**
     * @test
     */
    public function onBeforeSuite_starts_registry(): void
    {
        $this->assertFalse($this->registry->isStarted());

        $this->listener->onBeforeSuite($this->createBeforeSuiteEvent());

        $this->assertTrue($this->registry->isStarted());
    }

    /**
     * @test
     */
    public function onBeforeScenario_resets_kernel_booter(): void
    {
        $this->booter->expects($this->once())->method('reset');

        $this->listener->onBeforeScenario($this->createBeforeScenarioEvent());
    }

    /**
     * @test
     */
    public function onBeforeScenario_clears_registry(): void
    {
        $this->registry->start();
        $this->registry->register($this->createMock(Browser::class));
        $this->assertFalse($this->registry->isEmpty());

        $this->listener->onBeforeScenario($this->createBeforeScenarioEvent());

        $this->assertTrue($this->registry->isEmpty());
    }

    /**
     * @test
     */
    public function onAfterScenario_with_failed_result_saves_browser_state(): void
    {
        $this->registry->start();
        $this->listener->onBeforeScenario($this->createBeforeScenarioEvent());

        $browser = $this->createMock(Browser::class);
        $browser->expects($this->once())->method('saveCurrentState');
        $browser->method('savedArtifacts')->willReturn([]);
        $this->registry->register($browser);

        $this->listener->onAfterScenario($this->createAfterScenarioEvent(resultCode: TestResult::FAILED));
    }

    /**
     * @test
     */
    public function onAfterScenario_with_failed_result_saves_browser_state_with_formatted_name(): void
    {
        $this->registry->start();
        $this->listener->onBeforeScenario($this->createBeforeScenarioEvent(
            file: 'features/user_login.feature',
            line: 15,
            title: 'Logging in with valid credentials',
        ));

        $browser = $this->createMock(Browser::class);
        $browser->expects($this->once())
            ->method('saveCurrentState')
            ->with($this->callback(fn(string $filename): bool =>
                \str_starts_with($filename, 'failure_')
                && \str_contains($filename, 'features/user_login.feature')
                && \str_contains($filename, 'Logging in with valid credentials')
            ))
        ;
        $browser->method('savedArtifacts')->willReturn([]);
        $this->registry->register($browser);

        $this->listener->onAfterScenario($this->createAfterScenarioEvent(
            resultCode: TestResult::FAILED,
            file: 'features/user_login.feature',
            line: 15,
            title: 'Logging in with valid credentials',
        ));
    }

    /**
     * @test
     */
    public function onAfterScenario_with_failed_result_saves_state_for_each_registered_browser(): void
    {
        $this->registry->start();
        $this->listener->onBeforeScenario($this->createBeforeScenarioEvent());

        $browser1 = $this->createMock(Browser::class);
        $browser1->expects($this->once())->method('saveCurrentState');
        $browser1->method('savedArtifacts')->willReturn([]);
        $this->registry->register($browser1);

        $browser2 = $this->createMock(Browser::class);
        $browser2->expects($this->once())->method('saveCurrentState');
        $browser2->method('savedArtifacts')->willReturn([]);
        $this->registry->register($browser2);

        $this->listener->onAfterScenario($this->createAfterScenarioEvent(resultCode: TestResult::FAILED));
    }

    /**
     * @test
     */
    public function onAfterScenario_with_passed_result_does_not_save_browser_state(): void
    {
        $this->registry->start();
        $this->listener->onBeforeScenario($this->createBeforeScenarioEvent());

        $browser = $this->createMock(Browser::class);
        $browser->expects($this->never())->method('saveCurrentState');
        $browser->method('savedArtifacts')->willReturn([]);
        $this->registry->register($browser);

        $this->listener->onAfterScenario($this->createAfterScenarioEvent(resultCode: 0));
    }

    /**
     * @test
     */
    public function onAfterScenario_with_failed_result_clears_registry_after_finish(): void
    {
        $this->registry->start();
        $this->listener->onBeforeScenario($this->createBeforeScenarioEvent());

        $browser = $this->createMock(Browser::class);
        $browser->method('savedArtifacts')->willReturn([]);
        $this->registry->register($browser);

        $this->assertFalse($this->registry->isEmpty());

        $this->listener->onAfterScenario($this->createAfterScenarioEvent(resultCode: TestResult::FAILED));

        $this->assertTrue($this->registry->isEmpty());
    }

    /**
     * @test
     */
    public function onAfterScenario_with_passed_result_clears_registry_after_finish(): void
    {
        $this->registry->start();
        $this->listener->onBeforeScenario($this->createBeforeScenarioEvent());

        $browser = $this->createMock(Browser::class);
        $browser->method('savedArtifacts')->willReturn([]);
        $this->registry->register($browser);

        $this->assertFalse($this->registry->isEmpty());

        $this->listener->onAfterScenario($this->createAfterScenarioEvent(resultCode: 0));

        $this->assertTrue($this->registry->isEmpty());
    }

    /**
     * @test
     */
    public function onAfterScenario_with_empty_registry_and_failed_result_does_not_fail(): void
    {
        $this->registry->start();
        $this->listener->onBeforeScenario($this->createBeforeScenarioEvent());

        $this->listener->onAfterScenario($this->createAfterScenarioEvent(
            resultCode: TestResult::FAILED,
        ));

        $this->assertTrue($this->registry->isEmpty());
    }

    /**
     * @test
     */
    public function onAfterSuite_stops_registry(): void
    {
        $this->registry->start();
        $this->assertTrue($this->registry->isStarted());

        $this->listener->onAfterSuite($this->createAfterSuiteEvent());

        $this->assertFalse($this->registry->isStarted());
    }

    /**
     * @test
     */
    public function onAfterSuite_writes_summary_to_sink(): void
    {
        $this->sink->expects($this->once())->method('writeSummary');

        $this->listener->onAfterSuite($this->createAfterSuiteEvent());
    }

    /**
     * @test
     */
    public function onAfterSuite_without_started_registry_does_not_fail(): void
    {
        $this->sink->expects($this->once())->method('writeSummary');

        $this->listener->onAfterSuite($this->createAfterSuiteEvent());
    }

    private function createBeforeSuiteEvent(): BeforeSuiteTested
    {
        return new BeforeSuiteTested(
            $this->createMock(Environment::class),
            $this->createMock(SpecificationIterator::class),
        );
    }

    private function createAfterSuiteEvent(): AfterSuiteTested
    {
        return new AfterSuiteTested(
            $this->createMock(Environment::class),
            $this->createMock(SpecificationIterator::class),
            $this->createMock(TestResult::class),
            $this->createMock(Teardown::class),
        );
    }

    private function createBeforeScenarioEvent(?string $file = null, ?int $line = null, ?string $title = null): BeforeScenarioTested
    {
        return new BeforeScenarioTested(
            $this->createMock(Environment::class),
            $this->createFeature($file ?? 'features/test.feature'),
            $this->createScenario($line ?? 1, $title ?? 'Test scenario'),
        );
    }

    private function createAfterScenarioEvent(int $resultCode, ?string $file = null, ?int $line = null, ?string $title = null): AfterScenarioTested
    {
        $result = $this->createMock(TestResult::class);
        $result->method('getResultCode')->willReturn($resultCode);

        return new AfterScenarioTested(
            $this->createMock(Environment::class),
            $this->createFeature($file ?? 'features/test.feature'),
            $this->createScenario($line ?? 1, $title ?? 'Test scenario'),
            $result,
            $this->createMock(Teardown::class),
        );
    }

    private function createFeature(string $file): FeatureNode&MockObject
    {
        $feature = $this->createMock(FeatureNode::class);
        $feature->method('getFile')->willReturn($file);

        return $feature;
    }

    private function createScenario(int $line, string $title): ScenarioLikeInterface&MockObject
    {
        $scenario = $this->createMock(ScenarioLikeInterface::class);
        $scenario->method('getLine')->willReturn($line);
        $scenario->method('getTitle')->willReturn($title);

        return $scenario;
    }
}

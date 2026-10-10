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

use PHPUnit\Event\Code\Test;
use PHPUnit\Event\Test\Errored;
use PHPUnit\Event\Test\ErroredSubscriber;
use PHPUnit\Event\Test\Failed;
use PHPUnit\Event\Test\FailedSubscriber;
use PHPUnit\Event\Test\Finished as TestFinishedEvent;
use PHPUnit\Event\Test\FinishedSubscriber as TestFinishedSubscriber;
use PHPUnit\Event\Test\PreparationStarted as TestStartedEvent;
use PHPUnit\Event\Test\PreparationStartedSubscriber as TestStartedSubscriber;
use PHPUnit\Event\TestRunner\Finished as TestRunnerFinishedEvent;
use PHPUnit\Event\TestRunner\FinishedSubscriber as TestRunnerFinishedSubscriber;
use PHPUnit\Event\TestRunner\Started as TestRunnerStartedEvent;
use PHPUnit\Event\TestRunner\StartedSubscriber as TestRunnerStartedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class BrowserExtension implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $tracker = new ArtifactTracker();

        $facade->registerSubscriber(new class($tracker) implements TestRunnerStartedSubscriber {
            public function __construct(
                private ArtifactTracker $tracker,
            ) {
            }

            public function notify(TestRunnerStartedEvent $event): void
            {
                $this->tracker->executeBeforeFirstTest();
            }
        });

        $facade->registerSubscriber(new class($tracker) implements TestRunnerFinishedSubscriber {
            public function __construct(
                private ArtifactTracker $tracker,
            ) {
            }

            public function notify(TestRunnerFinishedEvent $event): void
            {
                $this->tracker->executeAfterLastTest();
            }
        });

        $facade->registerSubscriber(new class($tracker) implements TestStartedSubscriber {
            public function __construct(
                private ArtifactTracker $tracker,
            ) {
            }

            public function notify(TestStartedEvent $event): void
            {
                $this->tracker->executeBeforeTest($event->test()->name());
            }
        });

        $facade->registerSubscriber(new class($tracker) implements TestFinishedSubscriber {
            public function __construct(
                private ArtifactTracker $tracker,
            ) {
            }

            public function notify(TestFinishedEvent $event): void
            {
                $this->tracker->executeAfterTest(
                    $event->test()->name(),
                    (float) $event->telemetryInfo()->time()->seconds(),
                );
            }
        });

        $facade->registerSubscriber(new class($tracker) implements ErroredSubscriber {
            public function __construct(
                private ArtifactTracker $tracker,
            ) {
            }

            public function notify(Errored $event): void
            {
                $this->tracker->executeAfterTestError(
                    BrowserExtension::testName($event->test()),
                    $event->throwable()->message(),
                    (float) $event->telemetryInfo()->time()->seconds(),
                );
            }
        });

        $facade->registerSubscriber(new class($tracker) implements FailedSubscriber {
            public function __construct(
                private ArtifactTracker $tracker,
            ) {
            }

            public function notify(Failed $event): void
            {
                $this->tracker->executeAfterTestFailure(
                    BrowserExtension::testName($event->test()),
                    $event->throwable()->message(),
                    (float) $event->telemetryInfo()->time()->seconds(),
                );
            }
        });
    }

    /**
     * @internal
     */
    public static function testName(Test $test): string
    {
        if ($test->isTestMethod()) {
            return $test->nameWithClass();
        }

        return $test->name();
    }
}

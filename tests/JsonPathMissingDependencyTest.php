<?php

declare(strict_types=1);

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Tests;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zenstruck\Browser\Json;

/**
 * Lives apart from {@see JsonPathTest} because it may not load "symfony/json-path": a class cannot be
 * unloaded, so the guard is only reachable in a process where the crawler was never autoloaded.
 */
class JsonPathMissingDependencyTest extends TestCase
{
    /**
     * @test
     *
     * @runInSeparateProcess
     *
     * @preserveGlobalState disabled
     */
    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function opting_in_without_the_package_explains_what_to_install(): void
    {
        // built before the autoloaders go, so the class under test is loaded and the crawler is not
        $json = new Json('{"foo":"bar"}');
        $autoloaders = \spl_autoload_functions();
        $caught = null;

        foreach ($autoloaders as $autoloader) {
            \spl_autoload_unregister($autoloader);
        }

        try {
            $json->usingJsonPath();
        } catch (\LogicException $e) {
            $caught = $e;
        } finally {
            // restored before asserting: PHPUnit autoloads its constraints lazily
            foreach ($autoloaders as $autoloader) {
                \spl_autoload_register($autoloader);
            }
        }

        self::assertNotNull($caught, 'Expected a LogicException naming the missing package.');
        self::assertStringContainsString('"symfony/json-path" is required', $caught->getMessage());
    }
}

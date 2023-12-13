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
use Zenstruck\Browser;
use Zenstruck\Browser\Test\LegacyExtension;

final class NormalizationTest extends TestCase
{
    public static function namesProvider(): \Generator
    {
        $baseTemplate = 'error_' . __METHOD__;

        yield 'test name without datasets' => [
            'test name' => __METHOD__,
            'expected output' => \strtr($baseTemplate, '\\:', '-_') . '__0',
        ];

        $datasetTemplate = $baseTemplate . '__data-set-%s__0';

        $alphaTemplate = sprintf($datasetTemplate, 'test-set', '');
        $alphaOutput = \strtr($alphaTemplate, '\\:', '-_');

        $numericTemplate = sprintf($datasetTemplate, '0', '');
        $numericOutput = \strtr($numericTemplate, '\\:', '-_');

        yield 'phpunit 10 alpha' => [
            'test name' => __METHOD__ . ' with data set "test set"',
            'expected output' => $alphaOutput,
        ];
        yield 'phpunit 10 numeric' => [
            'test name' => __METHOD__ . ' with data set #0',
            'expected output' => $numericOutput,
        ];
        yield 'legacy alpha' => [
            'test name' => __METHOD__ . ' with data set "test set" (test set)',
            'expected output' => $alphaOutput,
        ];
        yield 'legacy numeric' => [
            'test name' => __METHOD__ . ' with data set #0 (test set)',
            'expected output' => $numericOutput,
        ];
    }

    /**
     * @test
     * @dataProvider namesProvider
     */
    public function can_normalize_test_names(string $testName, string $normalizedName): void
    {
        $browser = $this->createMock(Browser::class);
        $browser
            ->expects(self::once())
            ->method('saveCurrentState')
            ->with($normalizedName);

        $extension = new LegacyExtension();
        $extension->executeBeforeFirstTest();
        $extension->executeBeforeTest($testName);
        $extension::registerBrowser($browser);
        $extension->executeAfterTestError($testName, '', 0);
    }
}

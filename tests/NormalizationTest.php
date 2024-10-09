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
    /**
     * @test
     * @dataProvider namesProvider
     * @dataProvider edgeCaseTestNames
     */
    public function can_normalize_test_names(string $testName, string $expectedOutput): void
    {
        $browser = $this->createMock(Browser::class);
        $browser
            ->expects(self::once())
            ->method('saveCurrentState')
            ->with($expectedOutput);

        $extension = new LegacyExtension();
        $extension->executeBeforeFirstTest();
        $extension->executeBeforeTest($testName);
        $extension::registerBrowser($browser);
        $extension->executeAfterTestError($testName, '', 0);
    }

    public static function namesProvider(): \Generator
    {
        $baseTemplate = 'error_'.__METHOD__;

        yield 'test name without datasets' => [
            'testName' => __METHOD__,
            'expectedOutput' => \strtr($baseTemplate, '\\:', '-_').'__0',
        ];

        $datasetTemplate = $baseTemplate.'__data-set-%s__0';

        $alphaTemplate = \sprintf($datasetTemplate, 'test-set', '');
        $alphaOutput = \strtr($alphaTemplate, '\\:', '-_');

        $numericTemplate = \sprintf($datasetTemplate, '0', '');
        $numericOutput = \strtr($numericTemplate, '\\:', '-_');

        yield 'phpunit 10 alpha' => [
            'testName' => __METHOD__.' with data set "test set"',
            'expectedOutput' => $alphaOutput,
        ];
        yield 'phpunit 10 numeric' => [
            'testName' => __METHOD__.' with data set #0',
            'expectedOutput' => $numericOutput,
        ];
        yield 'legacy alpha' => [
            'testName' => __METHOD__.' with data set "test set" (test set)',
            'expectedOutput' => $alphaOutput,
        ];
        yield 'legacy numeric' => [
            'testName' => __METHOD__.' with data set #0 (test set)',
            'expectedOutput' => $numericOutput,
        ];
    }

    public static function edgeCaseTestNames(): \Generator
    {
        $baseTemplate = \strtr('error_'.__METHOD__.'__data-set-', '\\:', '-_');
        yield 'self within moustache' => [
            'testName' => __METHOD__.' with data set "te{{self}}st" (test set)',
            'expectedOutput' => $baseTemplate.'te-self-st__0',
        ];
        yield 'double quoted with space' => [
            'testName' => __METHOD__.' with data set "_self.env.setCache("uri://host.net:2121") _self.env.loadTemplate("other-host")" (test set)',
            'expectedOutput' => $baseTemplate.'_self-env-setCache-uri-host-net-2121-_self-env-loadTemplate-other-host-__0',
        ];
        yield 'double quotes in moustache' => [
            'testName' => __METHOD__.' with data set "te{{_self.env.registerUndefinedFilterCallback("exec")}}{{_self.env.getFilter("id")}}st"',
            'expectedOutput' => $baseTemplate.'te-_self-env-registerUndefinedFilterCallback-exec-_self-env-getFilter-id-st__0',
        ];
        yield 'escaped simple quote' => [
            'testName' => __METHOD__.' with data set "te{{\'/etc/passwd\'|file_excerpt(1,30)}}st"',
            'expectedOutput' => $baseTemplate.'te-etc-passwd-file_excerpt-1-30-st__0',
        ];
        yield 'single quote for array index access' => [
            'testName' => __METHOD__.' with data set "te{{[\'id\']|filter(\'system\')}}st"',
            'expectedOutput' => $baseTemplate.'te-id-filter-system-st__0',
        ];
        yield 'numeric array access' => [
            'testName' => __METHOD__.' with data set "te{{[0]|reduce(\'system\',\'id\')}}st"',
            'expectedOutput' => $baseTemplate.'te-0-reduce-system-id-st__0',
        ];
    }
}

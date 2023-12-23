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
            'test name' => __METHOD__,
            'expected output' => \strtr($baseTemplate, '\\:', '-_').'__0',
        ];

        $datasetTemplate = $baseTemplate.'__data-set-%s__0';

        $alphaTemplate = \sprintf($datasetTemplate, 'test-set', '');
        $alphaOutput = \strtr($alphaTemplate, '\\:', '-_');

        $numericTemplate = \sprintf($datasetTemplate, '0', '');
        $numericOutput = \strtr($numericTemplate, '\\:', '-_');

        yield 'phpunit 10 alpha' => [
            'test name' => __METHOD__.' with data set "test set"',
            'expected output' => $alphaOutput,
        ];
        yield 'phpunit 10 numeric' => [
            'test name' => __METHOD__.' with data set #0',
            'expected output' => $numericOutput,
        ];
        yield 'legacy alpha' => [
            'test name' => __METHOD__.' with data set "test set" (test set)',
            'expected output' => $alphaOutput,
        ];
        yield 'legacy numeric' => [
            'test name' => __METHOD__.' with data set #0 (test set)',
            'expected output' => $numericOutput,
        ];
    }

    public static function edgeCaseTestNames(): \Generator
    {
        $baseTemplate = \strtr('error_'.__METHOD__.'__data-set-', '\\:', '-_');
        yield 'self within moustache' => [
            'test name' => __METHOD__.' with data set "te{{self}}st" (test set)',
            'expected output' => $baseTemplate.'te-self-st__0',
        ];
        yield 'double quoted with space' => [
            'test name' => __METHOD__.' with data set "_self.env.setCache("uri://host.net:2121") _self.env.loadTemplate("other-host")" (test set)',
            'expected output' => $baseTemplate.'_self-env-setCache-uri-host-net-2121-_self-env-loadTemplate-other-host-__0',
        ];
        yield 'double quotes in moustache' => [
            'test name' => __METHOD__.' with data set "te{{_self.env.registerUndefinedFilterCallback("exec")}}{{_self.env.getFilter("id")}}st"',
            'expected output' => $baseTemplate.'te-_self-env-registerUndefinedFilterCallback-exec-_self-env-getFilter-id-st__0',
        ];
        yield 'escaped simple quote' => [
            'test name' => __METHOD__.' with data set "te{{\'/etc/passwd\'|file_excerpt(1,30)}}st"',
             'expected output' => $baseTemplate.'te-etc-passwd-file_excerpt-1-30-st__0',
         ];
        yield 'single quote for array index access' => [
            'test name' => __METHOD__.' with data set "te{{[\'id\']|filter(\'system\')}}st"',
            'expected output' => $baseTemplate.'te-id-filter-system-st__0',
        ];
        yield 'numeric array access' => [
            'test name' => __METHOD__.' with data set "te{{[0]|reduce(\'system\',\'id\')}}st"',
            'expected output' => $baseTemplate.'te-0-reduce-system-id-st__0',
        ];
    }
}

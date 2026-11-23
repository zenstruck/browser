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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zenstruck\Browser\Json;

/**
 * BROWSER_JSON_ENGINE selects the engine for the whole suite.
 *
 * It is what reaches the places that build a {@see Json} themselves, where there is no call to chain
 * usingJsonPath() onto: KernelBrowser::assertJsonMatches(), Session::dump(), and the instance handed
 * to a use() callback.
 */
class JsonEngineTest extends TestCase
{
    protected function setUp(): void
    {
        unset($_ENV['BROWSER_JSON_ENGINE'], $_SERVER['BROWSER_JSON_ENGINE']);
    }

    protected function tearDown(): void
    {
        unset($_ENV['BROWSER_JSON_ENGINE'], $_SERVER['BROWSER_JSON_ENGINE']);
    }

    /**
     * @test
     */
    #[Test]
    public function jmespath_is_the_default(): void
    {
        (new Json('{"foo":{"bar":"baz"}}'))
            ->assertThat('foo.bar', static function(Json $json) { $json->equals('baz'); })
        ;
    }

    /**
     * No usingJsonPath() anywhere: the expression is resolved as JSONPath because the variable says so.
     *
     * @test
     *
     * @dataProvider jsonPathSpellings
     */
    #[Test]
    #[DataProvider('jsonPathSpellings')]
    public function the_variable_selects_json_path_for_an_instance_that_never_opted_in(string $spelling): void
    {
        $_SERVER['BROWSER_JSON_ENGINE'] = $spelling;

        (new Json('{"foo":{"bar":"baz"}}'))
            ->assertThat('$.foo.bar', static function(Json $json) { $json->equals('baz'); })
        ;
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function jsonPathSpellings(): iterable
    {
        yield 'underscored' => ['json_path'];
        yield 'run together' => ['jsonpath'];
        yield 'hyphenated' => ['json-path'];
        yield 'upper case' => ['JSON_PATH'];
    }

    /**
     * @test
     */
    #[Test]
    public function the_variable_is_read_from_env_as_well_as_server(): void
    {
        $_ENV['BROWSER_JSON_ENGINE'] = 'json_path';

        (new Json('{"foo":{"bar":"baz"}}'))
            ->assertThat('$.foo.bar', static function(Json $json) { $json->equals('baz'); })
        ;
    }

    /**
     * @test
     */
    #[Test]
    public function naming_jmespath_leaves_it_selected(): void
    {
        $_SERVER['BROWSER_JSON_ENGINE'] = 'jmespath';

        (new Json('{"foo":{"bar":"baz"}}'))
            ->assertThat('foo.bar', static function(Json $json) { $json->equals('baz'); })
        ;
    }

    /**
     * A typo must not quietly run the suite on the engine the author was trying to leave.
     *
     * @test
     */
    #[Test]
    public function an_unrecognised_engine_is_refused_and_names_both(): void
    {
        $_SERVER['BROWSER_JSON_ENGINE'] = 'jsonpth';

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('"jsonpth" is not a valid BROWSER_JSON_ENGINE: expected "jmespath" or "json_path".');

        new Json('{"foo":"bar"}');
    }

    /**
     * @test
     */
    #[Test]
    public function an_empty_variable_leaves_the_default_alone(): void
    {
        $_SERVER['BROWSER_JSON_ENGINE'] = '   ';

        (new Json('{"foo":{"bar":"baz"}}'))
            ->assertThat('foo.bar', static function(Json $json) { $json->equals('baz'); })
        ;
    }

    /**
     * usingJsonPath() still works when the variable has already selected it, so a suite that sets the
     * variable does not have to strip the per-instance calls.
     *
     * @test
     */
    #[Test]
    public function opting_in_on_top_of_the_variable_is_harmless(): void
    {
        $_SERVER['BROWSER_JSON_ENGINE'] = 'json_path';

        (new Json('{"foo":{"bar":"baz"}}'))->usingJsonPath()
            ->assertThat('$.foo.bar', static function(Json $json) { $json->equals('baz'); })
        ;
    }
}

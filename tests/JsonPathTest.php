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

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\JsonPath\Exception\JsonCrawlerException;
use Symfony\Component\JsonPath\JsonCrawler;
use Zenstruck\Browser\Json;

class JsonPathTest extends TestCase
{
    protected function setUp(): void
    {
        if (!\class_exists(JsonCrawler::class)) {
            self::markTestSkipped('symfony/json-path is not installed.');
        }
    }

    /** @test */
    #[Test]
    public function the_default_engine_is_unchanged(): void
    {
        $jmesPath = new Json('{"foo":"bar"}');

        self::assertSame('bar', $jmesPath->search('foo'));
        self::assertSame(['bar'], $jmesPath->usingJsonPath()->search('$.foo'));

        // the opt-in returns a new instance, it does not switch the original over
        self::assertSame('bar', $jmesPath->search('foo'));
    }

    /**
     * @test
     *
     * @dataProvider selectorExistsProvider
     */
    #[Test]
    #[DataProvider('selectorExistsProvider')]
    public function assert_has_passes_if_selector_exists(string $json, string $selector): void
    {
        (new Json($json))->usingJsonPath()->assertHas($selector);
    }

    /**
     * @test
     *
     * @dataProvider selectorDoesNotExistProvider
     */
    #[Test]
    #[DataProvider('selectorDoesNotExistProvider')]
    public function assert_has_fails_if_selector_does_not_exist(string $json, string $selector): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("Element with selector \"{$selector}\" not found.");

        (new Json($json))->usingJsonPath()->assertHas($selector);
    }

    /**
     * @test
     *
     * @dataProvider selectorDoesNotExistProvider
     */
    #[Test]
    #[DataProvider('selectorDoesNotExistProvider')]
    public function assert_missing_passes_if_selector_does_not_exist(string $json, string $selector): void
    {
        (new Json($json))->usingJsonPath()->assertMissing($selector);
    }

    /**
     * @test
     *
     * @dataProvider selectorExistsProvider
     */
    #[Test]
    #[DataProvider('selectorExistsProvider')]
    public function assert_missing_fails_if_selector_exists(string $json, string $selector): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("Element with selector \"{$selector}\" exists but it should not.");

        (new Json($json))->usingJsonPath()->assertMissing($selector);
    }

    public static function selectorExistsProvider(): iterable
    {
        yield ['{"foo":"bar"}', '$.foo'];
        yield ['{"foo":{"bar": "baz"}}', '$.foo.bar'];
        yield ['[{"foo":"bar"}]', '$[0].foo'];
        yield ['{"foo":[{"bar": "baz"}]}', '$.foo[0].bar'];
        yield ['{"foo": 1}', '$.foo'];
        yield ['{"foo": ""}', '$.foo'];
        yield ['{"foo": 0}', '$.foo'];
        yield ['{"foo": false}', '$.foo'];
        yield ['{"foo": null}', '$.foo'];
        yield ['{"foo": {"bar": null}}', '$.foo.bar'];
        yield ['{"hydra:totalItems": 0}', "$['hydra:totalItems']"];
    }

    public static function selectorDoesNotExistProvider(): iterable
    {
        yield ['{}', '$.foo'];
        yield ['{"foo":"bar"}', '$.bar'];
        yield ['{"foo":{"bar": "baz"}}', '$.foo.baz'];
        yield ['{"foo":[]}', '$.foo[*]'];
        yield ['', '$.foo'];
    }

    /**
     * @test
     *
     * @dataProvider matchesProvider
     */
    #[Test]
    #[DataProvider('matchesProvider')]
    public function can_assert_a_query_matches(string $json, string $expression, array $expected): void
    {
        (new Json($json))->usingJsonPath()->assertMatches($expression, $expected);
    }

    public static function matchesProvider(): iterable
    {
        // a query selects a LIST of nodes, so a single match is a one-element list
        yield ['{"foo":"bar"}', '$.foo', ['bar']];
        yield ['{"foo":{"bar":"baz"}}', '$.foo', [['bar' => 'baz']]];
        yield ['{"foo":[{"bar":1},{"bar":2}]}', '$.foo[*].bar', [1, 2]];
        yield ['{"foo":"bar","bar":"baz"}', '$', [['foo' => 'bar', 'bar' => 'baz']]];
        yield ['{"foo":"bar"}', '$.missing', []];
    }

    /** @test */
    #[Test]
    public function can_perform_assertion_on_a_selected_node(): void
    {
        (new Json('{"foo":{"bar":"baz"}}'))->usingJsonPath()
            ->assertThat('$.foo.bar', static function(Json $json) { $json->isNotEmpty()->equals(['baz']); })
        ;
    }

    /**
     * The {@see Json} handed to the callback keeps the JSONPath engine; were it to fall back to the
     * default, the expression below would reach JMESPath, which rejects a leading "$".
     *
     * @test
     */
    #[Test]
    public function a_nested_assertion_keeps_the_json_path_engine(): void
    {
        (new Json('{"foo":{"bar":"baz"}}'))->usingJsonPath()
            ->assertThat('$.foo', static function(Json $json) { $json->assertMatches('$[0].bar', ['baz']); })
        ;
    }

    /**
     * @test
     *
     * @dataProvider eachProvider
     */
    #[Test]
    #[DataProvider('eachProvider')]
    public function can_perform_assertion_on_each_selected_node(string $json, string $expression, callable $asserter): void
    {
        (new Json($json))->usingJsonPath()->assertThatEach($expression, $asserter);
    }

    public static function eachProvider(): iterable
    {
        yield ['{"foo":[1, 2]}', '$.foo[*]', static function(Json $json) { $json->isGreaterThan(0); }];
        yield ['{"foo":[{"bar": 1}, {"bar": 2}]}', '$.foo[*].bar', static function(Json $json) { $json->isGreaterThan(0); }];
    }

    /**
     * @test
     *
     * @dataProvider noNodesProvider
     */
    #[Test]
    #[DataProvider('noNodesProvider')]
    public function assert_that_each_fails_if_no_node_is_selected(string $json, string $expression): void
    {
        $this->expectException(AssertionFailedError::class);

        (new Json($json))->usingJsonPath()->assertThatEach($expression, static function(Json $json) {});
    }

    public static function noNodesProvider(): iterable
    {
        yield ['{}', '$.foo[*]'];
        yield ['{"foo": []}', '$.foo[*]'];
    }

    /** @test */
    #[Test]
    public function an_invalid_expression_reports_the_syntax_error(): void
    {
        $this->expectException(JsonCrawlerException::class);

        (new Json('{"foo":"bar"}'))->usingJsonPath()->search('foo.bar');
    }
}

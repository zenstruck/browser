<?php

declare(strict_types=1);

namespace Zenstruck\Browser\Tests;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use Zenstruck\Assert;
use Zenstruck\Browser\Json;

class JsonTest extends TestCase
{
    /**
     * @test
     * @dataProvider selectorExistsProvider
     */
    public function assert_has_passes_if_selector_exists(string $json, string $selector): void
    {
        try {
            (new Json($json))->assertHas($selector);
        } catch (AssertionFailedError $exception) {
            Assert::fail("assertHas() did not assert that selector \"{$selector}\" exists.");
        }
    }

    /**
     * @test
     * @dataProvider selectorDoesNotExistProvider
     */
    public function assert_has_fails_if_selector_does_not_exist(string $json, string $selector): void
    {
        try {
            (new Json($json))->assertHas($selector);
        } catch (AssertionFailedError $exception) {
            Assert::pass();

            return;
        }

        Assert::fail("assertHas() asserted that selector \"{$selector}\" exists although it does not.");
    }

    /**
     * @test
     * @dataProvider selectorDoesNotExistProvider
     */
    public function assert_missing_passes_if_selector_does_not_exist(string $json, string $selector): void
    {
        try {
            (new Json($json))->assertMissing($selector);
        } catch (AssertionFailedError $exception) {
            Assert::fail("assertMissing() asserted that selector \"{$selector}\" is missing although it does not.");
        }
    }

    /**
     * @test
     * @dataProvider selectorExistsProvider
     */
    public function assert_missing_fails_if_selector_exists(string $json, string $selector): void
    {
        try {
            (new Json($json))->assertMissing($selector);
        } catch (AssertionFailedError $exception) {
            Assert::pass();

            return;
        }

        Assert::fail("assertMissing() did not assert that \"{$selector}\" is missing.");
    }

    public function selectorExistsProvider(): iterable
    {
        yield ['{"foo":"bar"}', 'foo'];
        yield ['{"foo":{"bar": "baz"}}', 'foo.bar'];
        yield ['[{"foo":"bar"}]', '[0].foo'];
        yield ['{"foo":[{"bar": "baz"}]}', 'foo[0].bar'];
    }

    public function selectorDoesNotExistProvider(): iterable
    {
        yield ['{}', 'foo'];
        yield ['{"foo":"bar"}', 'bar'];
        yield ['{"foo":{"bar": "baz"}}', 'foo.baz'];
    }

    /**
     * @test
     * @dataProvider selectHasCountProvider
     */
    public function can_assert_a_selector_has_count(string $json, int $expectedCount): void
    {
        try {
            (new Json($json))->hasCount($expectedCount);
        } catch (AssertionFailedError $exception) {
            Assert::fail("assertCount() did not assert that json matches expected count \"{$expectedCount}\"");
        }
    }

    public function selectHasCountProvider(): iterable
    {
        yield ['[]', 0];
        yield ['[1,2,3]', 3];
    }

    /**
     * @test
     */
    public function can_perform_assertions_on_itself(): void
    {
        (new Json('["foo","bar"]'))->contains('bar')->doesNotContain('food');
    }

    /**
     * @test
     * @dataProvider scalarChildAssertionProvider
     */
    public function can_perform_assertion_on_scalar_child(string $selector, callable $asserter): void
    {
        (new Json('{"foo":{"bar":"baz"}}'))->assertThat($selector, $asserter);
    }

    public function scalarChildAssertionProvider(): iterable
    {
        yield ['noop', function(Json $json) {$json->isNull(); }];
        yield ['foo.bar', function(Json $json) {$json->isNotEmpty()->equals('baz'); }];
    }

    /**
     * @test
     * @dataProvider arrayChildAssertionProvider
     */
    public function can_perform_assertion_on_array_child(string $json, string $selector, callable $asserter): void
    {
        (new Json($json))->assertThatEach($selector, $asserter);
    }

    public function arrayChildAssertionProvider(): iterable
    {
        yield ['{"foo":[1, 2]}', 'foo', function(Json $json) {$json->isGreaterThan(0); }];
        yield ['{"foo":[{"bar": 1}, {"bar": 2}]}', 'foo[*].bar', function(Json $json) {$json->isGreaterThan(0); }];
    }

    /**
     * @test
     * @dataProvider invalidArrayChildAssertionProvider
     */
    public function assert_that_each_throws_if_invalid_array_given(string $json, string $selector, callable $asserter): void
    {
        try {
            (new Json($json))->assertThatEach($selector, $asserter);
        } catch (AssertionFailedError $e) {
            Assert::pass();

            return;
        }

        Assert::fail('Json::assertThatEach() should raise exception with invalid arrays.');
    }

    public function invalidArrayChildAssertionProvider(): iterable
    {
        yield ['{}', 'foo', static function(Json $json) {}];
        yield ['{"foo": "bar"}', 'foo', static function(Json $json) {}];
        yield ['{"foo": []}', 'foo', static function(Json $json) {}];
    }
}

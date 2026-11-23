<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser;

use JsonSchema\Validator;
use Symfony\Component\JsonPath\JsonCrawler;
use Zenstruck\Assert;
use Zenstruck\Assert\Expectation;

use function JmesPath\search;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @mixin Expectation
 */
final class Json implements \Stringable
{
    private const ENGINE_ENV_VAR = 'BROWSER_JSON_ENGINE';
    private const ENGINE_JMESPATH = 'jmespath';
    private const ENGINE_JSON_PATH = 'json_path';

    private string $source;

    /** @var mixed */
    private $decoded;

    private bool $jsonPath = false;

    public function __construct(string $source)
    {
        $this->source = $source;

        if (self::jsonPathIsTheConfiguredEngine()) {
            self::assertJsonPathIsAvailable();

            $this->jsonPath = true;
        }
    }

    /**
     * @param array<mixed> $arguments
     */
    public function __call(string $methodName, array $arguments): self
    {
        if (!\method_exists(Expectation::class, $methodName)) {
            throw new \BadMethodCallException("{$methodName} does not exist");
        }

        Assert::that($this->decoded())->{$methodName}(...$arguments);

        return $this;
    }

    public function __toString(): string
    {
        return \json_encode($this->decoded(), \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR);
    }

    /**
     * Returns a new instance that resolves expressions with JSONPath (RFC 9535) instead of JMESPath.
     *
     * A JSONPath query selects a *list* of nodes, so every method below works on that list: a query
     * matching a single node yields a one-element list, and one matching nothing yields an empty one.
     *
     * Requires "symfony/json-path".
     */
    public function usingJsonPath(): self
    {
        self::assertJsonPathIsAvailable();

        $clone = clone $this;
        $clone->jsonPath = true;

        return $clone;
    }

    /**
     * @param string $expression JMESPath expression, or JSONPath expression with {@see usingJsonPath()}
     * @param mixed  $expected   with JSONPath, the list of selected nodes
     */
    public function assertMatches(string $expression, $expected): self
    {
        Assert::that($this->search($expression))->equals($expected);

        return $this;
    }

    /**
     * @param string $selector JMESPath selector, or JSONPath expression with {@see usingJsonPath()}
     */
    public function assertHas(string $selector): self
    {
        if ($this->jsonPath) {
            Assert::that([] !== $this->findWithJsonPath($selector))
                ->equals(true, 'Element with selector "{selector}" not found.', ['selector' => $selector])
            ;

            return $this;
        }

        $prefix = 1 === \preg_match('#^\[#', $selector) ? '' : '@.';
        $exploded = \explode('.', $prefix.$selector);
        $key = \array_pop($exploded);
        $keySelector = \implode('.', $exploded);

        Assert::that($this->search("contains(keys({$keySelector}), '{$key}')"))
            ->equals(true, 'Element with selector "{selector}" not found.', ['selector' => $selector])
        ;

        return $this;
    }

    /**
     * @param string $selector JMESPath selector, or JSONPath expression with {@see usingJsonPath()}
     */
    public function assertMissing(string $selector): self
    {
        if ($this->jsonPath) {
            Assert::that([] !== $this->findWithJsonPath($selector))
                ->equals(false, 'Element with selector "{selector}" exists but it should not.', ['selector' => $selector])
            ;

            return $this;
        }

        $prefix = 1 === \preg_match('#^\[#', $selector) ? '' : '@.';
        $exploded = \explode('.', $prefix.$selector);
        $key = \array_pop($exploded);
        $keySelector = \implode('.', $exploded);

        Assert::that($this->search("contains(keys({$keySelector}), '{$key}')"))
            ->equals(false, 'Element with selector "{selector}" exists but it should not.', ['selector' => $selector])
        ;

        return $this;
    }

    /**
     * The callback takes one value, so with JSONPath the query must select exactly one node and the
     * callback is handed that node - not the one-element list holding it, against which every
     * expectation would be an expectation about a list.
     *
     * @param callable(Json):mixed $assert
     */
    public function assertThat(string $selector, callable $assert): self
    {
        if ($this->jsonPath) {
            $nodes = $this->findWithJsonPath($selector);

            Assert::that(\count($nodes))->equals(
                1,
                'Expected selector "{selector}" to select exactly one node, {count} selected.',
                ['selector' => $selector, 'count' => \count($nodes)],
            );

            $assert($this->encode($nodes[0]));

            return $this;
        }

        $assert($this->encode($this->search($selector)));

        return $this;
    }

    /**
     * With JSONPath the callback is called once per SELECTED NODE, which is what the expression
     * decides: `$.foo[*]` selects each element of `foo` and calls back for each, while `$.foo`
     * selects the one node holding the array and calls back once with it.
     *
     * @param callable(Json):mixed $assert
     */
    public function assertThatEach(string $selector, callable $assert): self
    {
        $value = $this->search($selector);

        if (!\is_array($value)) {
            Assert::fail('Value for selector "{selector}" is not an array.', ['selector' => $selector]);
        }

        Assert::that($value)->isNotEmpty();

        foreach ($value as $item) {
            $assert($this->encode($item));
        }

        return $this;
    }

    public function assertMatchesSchema(string $jsonSchema): self
    {
        if (!\class_exists(Validator::class)) {
            throw new \LogicException('"justinrainbow/json-schema" is required to check JSON schema (composer require --dev justinrainbow/json-schema".');
        }

        $validator = new Validator();
        $decoded = \json_decode($this->source, null, 512, \JSON_THROW_ON_ERROR);
        $validator->validate(
            $decoded,
            \json_decode($jsonSchema, true, 512, \JSON_THROW_ON_ERROR),
        );

        Assert::that($validator->isValid())->is(true, (string) \json_encode($validator->getErrors()));

        return $this;
    }

    /**
     * @param string $selector JMESPath selector, or JSONPath expression with {@see usingJsonPath()}
     *
     * @return mixed the selected value, or with JSONPath the list of selected nodes
     */
    public function search(string $selector)
    {
        if ($this->jsonPath) {
            return $this->findWithJsonPath($selector);
        }

        if (!\function_exists('JmesPath\search')) {
            throw new \LogicException('"mtdowling/jmespath.php" is required to search JSON (composer require --dev mtdowling/jmespath.php).');
        }

        return search($selector, $this->decoded());
    }

    /**
     * @return mixed
     */
    public function decoded()
    {
        if (isset($this->decoded)) {
            return $this->decoded;
        }

        if ('' === \trim($this->source)) {
            return null;
        }

        return $this->decoded = \json_decode($this->source, true, 512, \JSON_THROW_ON_ERROR);
    }

    /**
     * @param mixed $data
     */
    private function encode($data): self
    {
        $json = new self(\json_encode($data, \JSON_THROW_ON_ERROR));
        $json->jsonPath = $this->jsonPath;

        return $json;
    }

    /**
     * Whether BROWSER_JSON_ENGINE selects JSONPath for the whole suite.
     *
     * This is what reaches the places that build a Json themselves, where there is no call to chain
     * {@see usingJsonPath()} onto: KernelBrowser::assertJsonMatches(), Session::dump(), and the
     * instance handed to a use() callback.
     */
    private static function jsonPathIsTheConfiguredEngine(): bool
    {
        $engine = $_ENV[self::ENGINE_ENV_VAR] ?? $_SERVER[self::ENGINE_ENV_VAR] ?? null;

        if (!\is_string($engine) || '' === \trim($engine)) {
            return false;
        }

        // separators are ignored so "jsonpath", "json-path" and "json_path" all land, while a
        // genuine typo still throws rather than running the suite on the other engine
        return match (\str_replace(['_', '-'], '', \strtolower(\trim($engine)))) {
            'jsonpath' => true,
            'jmespath' => false,
            default => throw new \LogicException(\sprintf('"%s" is not a valid %s: expected "%s" or "%s".', $engine, self::ENGINE_ENV_VAR, self::ENGINE_JMESPATH, self::ENGINE_JSON_PATH)),
        };
    }

    private static function assertJsonPathIsAvailable(): void
    {
        if (!\class_exists(JsonCrawler::class)) {
            throw new \LogicException('"symfony/json-path" is required to search JSON with JSONPath (composer require --dev symfony/json-path).');
        }
    }

    /**
     * @return list<mixed>
     */
    private function findWithJsonPath(string $expression): array
    {
        self::assertJsonPathIsAvailable();

        if ('' === \trim($this->source)) {
            return [];
        }

        return (new JsonCrawler($this->source))->find($expression);
    }
}

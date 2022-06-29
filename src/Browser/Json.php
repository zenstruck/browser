<?php

namespace Zenstruck\Browser;

use Zenstruck\Assert;
use Zenstruck\Assert\Expectation;
use function JmesPath\search;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 * @mixin Expectation
 */
final class Json
{
    private string $source;

    public function __construct(string $source)
    {
        $this->source = $source;
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
     * @param string $expression JMESPath expression
     * @param mixed  $expected
     */
    public function assertMatches(string $expression, $expected): self
    {
        Assert::that($this->search($expression))->is($expected);

        return $this;
    }

    /**
     * @param string $selector JMESPath selector
     */
    public function assertHas(string $selector): self
    {
        Assert::try(fn() => $this->search("length({$selector})"));

        return $this;
    }

    /**
     * @param string $selector JMESPath selector
     */
    public function assertMissing(string $selector): self
    {
        try {
            $this->search("length({$selector})");
        } catch (\RuntimeException $e) {
            Assert::pass();

            return $this;
        }

        Assert::fail("Selector \"{$selector}\" does exists.");
    }

    /**
     * @param callable(Json):mixed $assert
     */
    public function assertThat(string $selector, callable $assert): self
    {
        $assert(self::encode($this->search($selector)));

        return $this;
    }

    /**
     * @param callable(Json):mixed $assert
     */
    public function assertThatEach(string $selector, callable $assert): void
    {
        $value = $this->search($selector);

        if (!\is_array($value)) {
            Assert::fail("Value for selector \"{$selector}\" is not an array.");
        }

        Assert::that($value)->isNotEmpty();

        foreach ($value as $item) {
            $assert(self::encode($item));
        }
    }

    /**
     * @return mixed
     */
    public function search(string $selector)
    {
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
        if (empty($this->source)) {
            return null;
        }

        return \json_decode($this->source, true, 512, \JSON_THROW_ON_ERROR);
    }

    /**
     * @param mixed $data
     */
    private static function encode($data): self
    {
        return new self(\json_encode($data, \JSON_THROW_ON_ERROR));
    }
}

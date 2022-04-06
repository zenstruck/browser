<?php

namespace Zenstruck\Browser;

use Zenstruck\Assert;
use function JmesPath\search;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Json
{
    private string $source;

    public function __construct(string $source)
    {
        $this->source = $source;
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
     * @return mixed
     */
    public function search(string $selector)
    {
        if (!\function_exists('JmesPath\search')) {
            throw new \LogicException('"mtdowling/jmespath.php" is required to search JSON (composer require mtdowling/jmespath.php).');
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
}

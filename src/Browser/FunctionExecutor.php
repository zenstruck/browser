<?php

namespace Zenstruck\Browser;

/**
 * Utility to manipulate and validate \Closure arguments.
 *
 * TODO extract to a library as zenstruck/foundry could benefit.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class FunctionExecutor
{
    private \ReflectionFunction $function;
    private int $minArguments = 0;
    private array $typeReplace = [];

    public function __construct(\ReflectionFunction $function)
    {
        $this->function = $function;
    }

    /**
     * @param callable|\ReflectionFunction $value
     */
    public static function createFor($value): self
    {
        if (\is_callable($value)) {
            $value = new \ReflectionFunction(\Closure::fromCallable($value));
        }

        if (!$value instanceof \ReflectionFunction) {
            throw new \InvalidArgumentException('$value must be callable or \ReflectionFunction');
        }

        return new self($value);
    }

    public function minArguments(int $min): self
    {
        $this->minArguments = $min;

        return $this;
    }

    public function replaceTypedArgument(string $typehint, $value): self
    {
        $this->typeReplace[$typehint] = $value;

        return $this;
    }

    public function replaceUntypedArgument($value): self
    {
        $this->typeReplace[null] = $value;

        return $this;
    }

    public function execute()
    {
        $arguments = $this->function->getParameters();

        if (\count($arguments) < $this->minArguments) {
            throw new \ArgumentCountError("{$this->minArguments} argument(s) required.");
        }

        $arguments = \array_map([$this, 'replaceArgument'], $arguments);

        return $this->function->invoke(...$arguments);
    }

    private function replaceArgument(\ReflectionParameter $argument)
    {
        $type = $argument->getType();

        if (!$type && \array_key_exists(null, $this->typeReplace)) {
            return $this->typeReplace[null];
        }

        if (!$type instanceof \ReflectionNamedType) {
            throw new \TypeError("Unable to replace argument \"{$argument->getName()}\".");
        }

        foreach (\array_keys($this->typeReplace) as $typehint) {
            if (!\is_a($type->getName(), $typehint, true)) {
                continue;
            }

            if (!($value = $this->typeReplace[$typehint]) instanceof \Closure) {
                return $value;
            }

            return $value($type->getName());
        }

        throw new \TypeError("Unable to replace argument \"{$argument->getName()}\".");
    }
}

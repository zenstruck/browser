<?php

namespace Zenstruck\Browser;

use Zenstruck\Browser\Assert\Assertion;
use Zenstruck\Browser\Assert\Assertion\Fail;
use Zenstruck\Browser\Assert\Assertion\IsTrue;
use Zenstruck\Browser\Assert\Assertion\MinkExpectation;
use Zenstruck\Browser\Assert\Exception\AssertionFailed;
use Zenstruck\Browser\Assert\Handler;
use Zenstruck\Browser\Assert\Handler\DefaultHandler;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Assert
{
    private static ?Handler $handler = null;

    public static function that(Assertion $assertion): void
    {
        try {
            $assertion();
        } catch (AssertionFailed $exception) {
            self::handler()->onFailure($exception);

            return;
        }

        self::handler()->onSuccess();
    }

    public static function true(bool $expression, string $message, ...$args): void
    {
        self::that(new IsTrue($expression, $message, ...$args));
    }

    public static function false(bool $expression, string $message, ...$args): void
    {
        self::that(new IsTrue(!$expression, $message, ...$args));
    }

    /**
     * @psalm-return never-returns
     */
    public static function fail(string $message, ...$args): void
    {
        self::that(new Fail($message, ...$args));
    }

    public static function pass(): void
    {
        self::handler()->onSuccess();
    }

    public static function wrapMinkExpectation(callable $callback): void
    {
        self::that(new MinkExpectation($callback));
    }

    public static function setHandler(Handler $handler): void
    {
        self::$handler = $handler;
    }

    private static function handler(): Handler
    {
        return self::$handler ??= new DefaultHandler();
    }
}

<?php

namespace Zenstruck;

use Behat\Mink\Driver\DriverInterface;
use Behat\Mink\Element\DocumentElement;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Behat\Mink\Mink;
use Behat\Mink\Session;
use Behat\Mink\WebAssert;
use Zenstruck\Browser\Actions;
use Zenstruck\Browser\Assertions;
use Zenstruck\Browser\Component;
use Zenstruck\Browser\FunctionExecutor;
use function JmesPath\search;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class Browser
{
    use Actions, Assertions;

    private const SESSION = 'app';

    private Mink $mink;

    public function __construct(DriverInterface $driver)
    {
        $this->mink = new Mink([self::SESSION => new Session($driver)]);
    }

    final public function minkSession(): Session
    {
        return $this->mink->getSession(self::SESSION);
    }

    final public function webAssert(): WebAssert
    {
        return $this->mink->assertSession(self::SESSION);
    }

    final public function documentElement(): DocumentElement
    {
        return $this->minkSession()->getPage();
    }

    final public function use(callable $callback): self
    {
        FunctionExecutor::createFor($callback)
            ->replaceUntypedArgument($this)
            ->replaceTypedArgument(self::class, $this)
            ->replaceTypedArgument(Component::class, fn(string $class) => new $class($this))
            ->execute()
        ;

        return $this;
    }

    final public function dump(?string $selector = null): self
    {
        $context = 'URL: '.$this->minkSession()->getCurrentUrl();

        try {
            $context .= ', STATUS: '.$this->minkSession()->getStatusCode();
        } catch (UnsupportedDriverActionException $e) {
        }

        dump($context, $this->normalizeDumpValue($selector), $context);

        return $this;
    }

    final public function dd(?string $selector = null): void
    {
        $this->dump($selector);
        exit(1);
    }

    private function normalizeDumpValue(?string $selector = null)
    {
        try {
            $contentType = $this->minkSession()->getResponseHeader('content-type');
        } catch (UnsupportedDriverActionException $e) {
            $contentType = null;
        }

        if (!str_contains((string) $contentType, 'application/json')) {
            return $selector ? $this->documentElement()->find('css', $selector)->getHtml() : $this->documentElement()->getContent();
        }

        $array = \json_decode($this->documentElement()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        if ($selector && \function_exists('JmesPath\search')) {
            return search($selector, $array);
        }

        return $selector ? $array[$selector] : $array;
    }
}

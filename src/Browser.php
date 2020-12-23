<?php

namespace Zenstruck;

use Behat\Mink\Driver\DriverInterface;
use Behat\Mink\Element\DocumentElement;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Mink;
use Behat\Mink\Session;
use Behat\Mink\WebAssert;
use PHPUnit\Framework\Assert as PHPUnit;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\VarDumper\VarDumper;
use Zenstruck\Browser\Component;
use Zenstruck\Browser\Response;
use Zenstruck\Browser\Test\Constraint\UrlMatches;
use Zenstruck\Browser\Util\FunctionExecutor;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class Browser
{
    private const SESSION = 'app';

    private Mink $mink;
    private ?string $sourceDir = null;

    public function __construct(DriverInterface $driver)
    {
        $this->mink = new Mink([self::SESSION => new Session($driver)]);
    }

    /**
     * @return static
     */
    public static function create(callable $factory): self
    {
        $browser = $factory();

        if (!$browser instanceof self) {
            throw new \RuntimeException(\sprintf('The factory callable must return an instance of "%s".', self::class));
        }

        return $browser;
    }

    /**
     * @return static
     */
    final public function setSourceDir(string $dir): self
    {
        $this->sourceDir = $dir;

        return $this;
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

    /**
     * @return static
     */
    final public function visit(string $uri): self
    {
        $this->minkSession()->visit($uri);

        return $this;
    }

    /**
     * @param array $parts The url parts to check (@see parse_url)
     *
     * @return static
     */
    final public function assertOn(string $expected, array $parts = ['path', 'query', 'fragment']): self
    {
        PHPUnit::assertThat($expected, new UrlMatches($this->minkSession()->getCurrentUrl(), $parts));

        return $this;
    }

    /**
     * @param array $parts The url parts to check (@see parse_url)
     *
     * @return static
     */
    final public function assertNotOn(string $expected, array $parts = ['path', 'query', 'fragment']): self
    {
        PHPUnit::assertThat(
            $expected,
            PHPUnit::logicalNot(new UrlMatches($this->minkSession()->getCurrentUrl(), $parts))
        );

        return $this;
    }

    /**
     * @return static
     */
    final public function assertContains(string $expected): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->responseContains($expected)
        );
    }

    /**
     * @return static
     */
    final public function assertNotContains(string $expected): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->responseNotContains($expected)
        );
    }

    /**
     * @return static
     */
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

    /**
     * @return static
     */
    final public function saveSource(string $filename): self
    {
        if ($this->sourceDir) {
            $filename = \sprintf('%s/%s', \rtrim($this->sourceDir, '/'), \ltrim($filename, '/'));
        }

        (new Filesystem())->dumpFile($filename, $this->response()->raw());

        return $this;
    }

    /**
     * @return static
     */
    final public function dump(?string $selector = null): self
    {
        VarDumper::dump($selector ? $this->response()->find($selector) : $this->response()->raw());

        return $this;
    }

    final public function dd(?string $selector = null): void
    {
        $this->dump($selector);
        exit(1);
    }

    public function dumpCurrentState(string $filename): void
    {
        $this->saveSource("{$filename}.txt");
    }

    protected function response(): Response
    {
        return Response::createFor($this->minkSession());
    }

    /**
     * @return static
     */
    final protected function wrapMinkExpectation(callable $callback): self
    {
        try {
            $callback();
            PHPUnit::assertTrue(true);
        } catch (ExpectationException $e) {
            PHPUnit::fail($e->getMessage());
        }

        return $this;
    }
}

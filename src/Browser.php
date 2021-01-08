<?php

namespace Zenstruck;

use Behat\Mink\Driver\DriverInterface;
use Behat\Mink\Element\DocumentElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Mink;
use Behat\Mink\Session;
use Behat\Mink\WebAssert;
use PHPUnit\Framework\Assert as PHPUnit;
use Symfony\Component\Filesystem\Filesystem;
use Zenstruck\Browser\Component;
use Zenstruck\Browser\Response;
use Zenstruck\Browser\Test\Constraint\UrlMatches;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class Browser
{
    private const SESSION = 'app';

    private Mink $mink;
    private ?string $sourceDir = null;
    private array $savedSources = [];

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
        Callback::createFor($callback)
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

        (new Filesystem())->dumpFile($this->savedSources[] = $filename, $this->response()->raw());

        return $this;
    }

    /**
     * @return static
     */
    final public function dump(?string $selector = null): self
    {
        $this->response()->dump($selector);

        return $this;
    }

    final public function dd(?string $selector = null): void
    {
        $this->dump($selector);
        $this->die();
    }

    /**
     * @return static
     */
    public function follow(string $link): self
    {
        $this->documentElement()->clickLink($link);

        return $this;
    }

    /**
     * @return static
     */
    final public function fillField(string $selector, string $value): self
    {
        $this->documentElement()->fillField($selector, $value);

        return $this;
    }

    /**
     * @return static
     */
    public function checkField(string $selector): self
    {
        $this->documentElement()->checkField($selector);

        return $this;
    }

    /**
     * @return static
     */
    public function uncheckField(string $selector): self
    {
        $this->documentElement()->uncheckField($selector);

        return $this;
    }

    /**
     * @return static
     */
    public function selectFieldOption(string $selector, string $value): self
    {
        $this->documentElement()->selectFieldOption($selector, $value);

        return $this;
    }

    /**
     * @return static
     */
    public function selectFieldOptions(string $selector, array $values): self
    {
        foreach ($values as $value) {
            $this->documentElement()->selectFieldOption($selector, $value, true);
        }

        return $this;
    }

    /**
     * @return static
     */
    final public function attachFile(string $selector, string $path): self
    {
        $this->documentElement()->attachFileToField($selector, $path);

        return $this;
    }

    /**
     * @return static
     */
    final public function click(string $selector): self
    {
        try {
            $this->documentElement()->pressButton($selector);
        } catch (ElementNotFoundException $e) {
            // try link
            $this->documentElement()->clickLink($selector);
        }

        return $this;
    }

    /**
     * @return static
     */
    final public function assertSee(string $expected): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->pageTextContains($expected)
        );
    }

    /**
     * @return static
     */
    final public function assertNotSee(string $expected): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->pageTextNotContains($expected)
        );
    }

    /**
     * @return static
     */
    final public function assertSeeIn(string $selector, string $expected): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->elementTextContains('css', $selector, $expected)
        );
    }

    /**
     * @return static
     */
    final public function assertNotSeeIn(string $selector, string $expected): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->elementTextNotContains('css', $selector, $expected)
        );
    }

    /**
     * @return static
     */
    final public function assertSeeElement(string $selector): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->elementExists('css', $selector)
        );
    }

    /**
     * @return static
     */
    final public function assertNotSeeElement(string $selector): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->elementNotExists('css', $selector)
        );
    }

    /**
     * @return static
     */
    final public function assertElementCount(string $selector, int $count): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->elementsCount('css', $selector, $count)
        );
    }

    /**
     * @return static
     */
    final public function assertFieldEquals(string $selector, string $expected): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->fieldValueEquals($selector, $expected)
        );
    }

    /**
     * @return static
     */
    final public function assertFieldNotEquals(string $selector, string $expected): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->fieldValueNotEquals($selector, $expected)
        );
    }

    /**
     * @return static
     */
    final public function assertSelected(string $selector, string $expected): self
    {
        try {
            $field = $this->webAssert()->fieldExists($selector);
            PHPUnit::assertTrue(true);
        } catch (ExpectationException $e) {
            PHPUnit::fail($e->getMessage());
        }

        PHPUnit::assertContains($expected, (array) $field->getValue());

        return $this;
    }

    /**
     * @return static
     */
    final public function assertNotSelected(string $selector, string $expected): self
    {
        try {
            $field = $this->webAssert()->fieldExists($selector);
            PHPUnit::assertTrue(true);
        } catch (ExpectationException $e) {
            PHPUnit::fail($e->getMessage());
        }

        PHPUnit::assertNotContains($expected, (array) $field->getValue());

        return $this;
    }

    /**
     * @return static
     */
    final public function assertChecked(string $selector): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->checkboxChecked($selector)
        );
    }

    /**
     * @return static
     */
    final public function assertNotChecked(string $selector): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->checkboxNotChecked($selector)
        );
    }

    /**
     * @return static
     */
    final public function assertElementAttributeContains(string $selector, string $attribute, string $expected): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->elementAttributeContains('css', $selector, $attribute, $expected)
        );
    }

    /**
     * @return static
     */
    final public function assertElementAttributeNotContains(string $selector, string $attribute, string $expected): self
    {
        return $this->wrapMinkExpectation(
            fn() => $this->webAssert()->elementAttributeNotContains('css', $selector, $attribute, $expected)
        );
    }

    /**
     * @internal
     */
    public function dumpCurrentState(string $filename): void
    {
        $this->saveSource("{$filename}.txt");
    }

    /**
     * @internal
     */
    public function savedArtifacts(): array
    {
        return ['Saved Source Files' => $this->savedSources];
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

    protected function die(): void
    {
        exit(1);
    }
}

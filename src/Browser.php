<?php

namespace Zenstruck;

use Behat\Mink\Driver\DriverInterface;
use Behat\Mink\Element\DocumentElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Mink;
use Behat\Mink\Session;
use Behat\Mink\WebAssert;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Filesystem\Filesystem;
use Zenstruck\Browser\Assertion\MinkAssertion;
use Zenstruck\Browser\Assertion\SameUrlAssertion;
use Zenstruck\Browser\Component;
use Zenstruck\Browser\Response;
use Zenstruck\Callback\Parameter;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class Browser
{
    private const SESSION = 'app';

    private Mink $mink;
    private ?string $sourceDir = null;
    private array $savedSources = [];

    /**
     * @internal
     */
    public function __construct(DriverInterface $driver)
    {
        $this->mink = new Mink([self::SESSION => new Session($driver)]);
    }

    /**
     * @return static
     */
    final public function setSourceDir(string $dir): self
    {
        $this->sourceDir = $dir;

        return $this;
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
     * @param array $parts The url parts to check {@see parse_url} (use empty array for "all")
     *
     * @return static
     */
    final public function assertOn(string $expected, array $parts = ['path', 'query', 'fragment']): self
    {
        Assert::run(new SameUrlAssertion($this->minkSession()->getCurrentUrl(), $expected, $parts));

        return $this;
    }

    /**
     * @param array $parts The url parts to check (@see parse_url)
     *
     * @return static
     */
    final public function assertNotOn(string $expected, array $parts = ['path', 'query', 'fragment']): self
    {
        Assert::not(new SameUrlAssertion($this->minkSession()->getCurrentUrl(), $expected, $parts));

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
        Callback::createFor($callback)->invokeAll(
            Parameter::union(
                Parameter::untyped($this),
                Parameter::typed(self::class, $this),
                Parameter::typed(Component::class, Parameter::factory(fn(string $class) => new $class($this))),
                Parameter::typed(Response::class, Parameter::factory(fn() => $this->response())),
                Parameter::typed(Crawler::class, Parameter::factory(fn() => $this->response()->ensureDom()->crawler()))
            )
        );

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
    final public function checkField(string $selector): self
    {
        $field = $this->documentElement()->findField($selector);

        if ($field && 'radio' === \mb_strtolower($field->getAttribute('type'))) {
            $this->documentElement()->selectFieldOption($selector, $field->getAttribute('value'));

            return $this;
        }

        $this->documentElement()->checkField($selector);

        return $this;
    }

    /**
     * @return static
     */
    final public function uncheckField(string $selector): self
    {
        $this->documentElement()->uncheckField($selector);

        return $this;
    }

    /**
     * Select Radio, check checkbox, select single/multiple values.
     *
     * @param string|array|null $value null: check radio/checkbox
     *                                 string: single value
     *                                 array: multiple values
     *
     * @return static
     */
    final public function selectField(string $selector, $value = null): self
    {
        if (\is_array($value)) {
            return $this->selectFieldOptions($selector, $value);
        }

        if (\is_string($value)) {
            return $this->selectFieldOption($selector, $value);
        }

        return $this->checkField($selector);
    }

    /**
     * @return static
     */
    final public function selectFieldOption(string $selector, string $value): self
    {
        $this->documentElement()->selectFieldOption($selector, $value);

        return $this;
    }

    /**
     * @return static
     */
    final public function selectFieldOptions(string $selector, array $values): self
    {
        foreach ($values as $value) {
            $this->documentElement()->selectFieldOption($selector, $value, true);
        }

        return $this;
    }

    /**
     * @param string[]|string $filename string: single file
     *                                  array: multiple files
     *
     * @return static
     */
    final public function attachFile(string $selector, $filename): self
    {
        foreach ((array) $filename as $file) {
            if (!\file_exists($file)) {
                throw new \InvalidArgumentException(\sprintf('File "%s" does not exist.', $file));
            }
        }

        $this->documentElement()->attachFileToField($selector, $filename);

        return $this;
    }

    /**
     * Click on a button, link or any DOM element.
     *
     * @return static
     */
    final public function click(string $selector): self
    {
        try {
            $this->documentElement()->pressButton($selector);
        } catch (ElementNotFoundException $e) {
            // try link
            try {
                $this->documentElement()->clickLink($selector);
            } catch (ElementNotFoundException $e) {
                $this->documentElement()->find('css', $selector)->click();
            }
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
        } catch (ExpectationException $e) {
            Assert::fail($e->getMessage());
        }

        Assert::that((array) $field->getValue())->contains($expected);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertNotSelected(string $selector, string $expected): self
    {
        try {
            $field = $this->webAssert()->fieldExists($selector);
        } catch (ExpectationException $e) {
            Assert::fail($e->getMessage());
        }

        Assert::that((array) $field->getValue())->doesNotContain($expected);

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

    public function response(): Response
    {
        return Response::createFor($this->minkSession());
    }

    /**
     * @internal
     */
    final protected function minkSession(): Session
    {
        return $this->mink->getSession(self::SESSION);
    }

    /**
     * @internal
     */
    final protected function webAssert(): WebAssert
    {
        return $this->mink->assertSession(self::SESSION);
    }

    /**
     * @internal
     */
    final protected function documentElement(): DocumentElement
    {
        return $this->minkSession()->getPage();
    }

    /**
     * @internal
     *
     * @return static
     */
    final protected function wrapMinkExpectation(callable $callback): self
    {
        Assert::run(new MinkAssertion($callback));

        return $this;
    }

    /**
     * @internal
     */
    protected function die(): void
    {
        exit(1);
    }
}

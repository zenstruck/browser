<?php

namespace Zenstruck;

use Behat\Mink\Element\NodeElement;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Filesystem\Filesystem;
use Zenstruck\Browser\Assertion\SameUrlAssertion;
use Zenstruck\Browser\Component;
use Zenstruck\Browser\Session;
use Zenstruck\Browser\Session\Driver;
use Zenstruck\Callback\Parameter;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class Browser
{
    private Session $session;
    private ?string $sourceDir;

    /** @var string[] */
    private array $savedSources = [];

    /**
     * @internal
     *
     * @param array<string,mixed> $options
     */
    public function __construct(Driver $driver, array $options = [])
    {
        $this->session = new Session($driver);
        $this->sourceDir = $options['source_dir'] ?? null;
    }

    final public function client(): AbstractBrowser
    {
        return $this->session->client();
    }

    /**
     * @return static
     */
    final public function visit(string $uri): self
    {
        $this->session()->request('GET', $uri);

        return $this;
    }

    /**
     * @param array $parts The url parts to check {@see parse_url} (use empty array for "all")
     *
     * @return static
     */
    final public function assertOn(string $expected, array $parts = ['path', 'query', 'fragment']): self
    {
        Assert::run(new SameUrlAssertion($this->session()->getCurrentUrl(), $expected, $parts));

        return $this;
    }

    /**
     * @param array $parts The url parts to check (@see parse_url)
     *
     * @return static
     */
    final public function assertNotOn(string $expected, array $parts = ['path', 'query', 'fragment']): self
    {
        Assert::not(new SameUrlAssertion($this->session()->getCurrentUrl(), $expected, $parts));

        return $this;
    }

    /**
     * @return static
     */
    final public function assertContains(string $expected): self
    {
        $this->session()->assert()->responseContains($expected);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertNotContains(string $expected): self
    {
        $this->session()->assert()->responseNotContains($expected);

        return $this;
    }

    final public function crawler(): Crawler
    {
        return $this->client()->getCrawler();
    }
    
    final public function content(): string
    {
        return $this->client()->getResponse()->getContent();
    }

    /**
     * @return static
     */
    final public function assertSee(string $expected): self
    {
        $this->session()->assert()->pageTextContains($expected);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertNotSee(string $expected): self
    {
        $this->session()->assert()->pageTextNotContains($expected);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertSeeIn(string $selector, string $expected): self
    {
        $this->session()->assert()->elementTextContains('css', $selector, $expected);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertNotSeeIn(string $selector, string $expected): self
    {
        $this->session()->assert()->elementTextNotContains('css', $selector, $expected);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertSeeElement(string $selector): self
    {
        $this->session()->assert()->elementExists('css', $selector);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertNotSeeElement(string $selector): self
    {
        $this->session()->assert()->elementNotExists('css', $selector);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertElementCount(string $selector, int $count): self
    {
        $this->session()->assert()->elementsCount('css', $selector, $count);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertElementAttributeContains(string $selector, string $attribute, string $expected): self
    {
        $this->session()->assert()->elementAttributeContains('css', $selector, $attribute, $expected);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertElementAttributeNotContains(string $selector, string $attribute, string $expected): self
    {
        $this->session()->assert()->elementAttributeNotContains('css', $selector, $attribute, $expected);

        return $this;
    }

    /**
     * @return static
     */
    final public function fillField(string $selector, string $value): self
    {
        $this->session()->page()->fillField($selector, $value);

        return $this;
    }

    /**
     * @return static
     */
    final public function checkField(string $selector): self
    {
        $field = $this->session()->page()->findField($selector);

        if ($field && 'radio' === \mb_strtolower((string) $field->getAttribute('type'))) {
            $this->session()->page()->selectFieldOption($selector, (string) $field->getAttribute('value'));

            return $this;
        }

        $this->session()->page()->checkField($selector);

        return $this;
    }

    /**
     * @return static
     */
    final public function uncheckField(string $selector): self
    {
        $this->session()->page()->uncheckField($selector);

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
        $this->session()->page()->selectFieldOption($selector, $value);

        return $this;
    }

    /**
     * @return static
     */
    final public function selectFieldOptions(string $selector, array $values): self
    {
        foreach ($values as $value) {
            $this->session()->page()->selectFieldOption($selector, $value, true);
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

        $this->session()->page()->attachFileToField($selector, $filename);

        return $this;
    }

    /**
     * Click on a button, link or any DOM element.
     *
     * @return static
     */
    final public function click(string $selector): self
    {
        $element = $this->getClickableElement($selector);

        $element->click();

        return $this;
    }

    /**
     * @return static
     */
    final public function assertFieldEquals(string $selector, string $expected): self
    {
        $this->session()->assert()->fieldValueEquals($selector, $expected);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertFieldNotEquals(string $selector, string $expected): self
    {
        $this->session()->assert()->fieldValueNotEquals($selector, $expected);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertSelected(string $selector, string $expected): self
    {
        $field = $this->session()->assert()->fieldExists($selector);

        Assert::that((array) $field->getValue())->contains($expected);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertNotSelected(string $selector, string $expected): self
    {
        $field = $this->session()->assert()->fieldExists($selector);

        Assert::that((array) $field->getValue())->doesNotContain($expected);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertChecked(string $selector): self
    {
        $this->session()->assert()->checkboxChecked($selector);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertNotChecked(string $selector): self
    {
        $this->session()->assert()->checkboxNotChecked($selector);

        return $this;
    }

    /**
     * @return static
     */
    final public function use(callable $callback): self
    {
        Callback::createFor($callback)->invokeAll(
            Parameter::union(...$this->useParameters())
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

        (new Filesystem())->dumpFile($this->savedSources[] = $filename, $this->session()->source());

        return $this;
    }

    /**
     * @return static
     */
    final public function dump(?string $selector = null): self
    {
        $this->session()->dump($selector);

        return $this;
    }

    final public function dd(?string $selector = null): void
    {
        $this->dump($selector)->session()->exit();
    }

    public function saveCurrentState(string $filename): void
    {
        $this->saveSource("{$filename}.html");
    }

    /**
     * @internal
     *
     * @return array<string,string[]>
     */
    public function savedArtifacts(): array
    {
        return ['Saved Source Files' => $this->savedSources];
    }

    final protected function getClickableElement(string $selector): NodeElement
    {
        // try button
        $element = $this->session()->page()->findButton($selector);

        if (!$element) {
            // try link
            $element = $this->session()->page()->findLink($selector);
        }

        if (!$element) {
            // try by css
            $element = $this->session()->page()->find('css', $selector);
        }

        if (!$element) {
            Assert::fail('Clickable element "%s" not found.', [$selector]);
        }

        if (!$element->isVisible()) {
            Assert::fail('Clickable element "%s" is not visible.', [$selector]);
        }

        if ($button = $this->session()->page()->findButton($selector)) {
            if (!$button->isVisible()) {
                Assert::fail('Button "%s" is not visible.', [$selector]);
            }
        }

        return $element;
    }

    /**
     * @internal
     */
    final protected function session(): Session
    {
        return $this->session;
    }

    /**
     * @internal
     *
     * @return Parameter[]
     */
    protected function useParameters(): array
    {
        return [
            Parameter::untyped($this),
            Parameter::typed(self::class, $this),
            Parameter::typed(Component::class, Parameter::factory(fn(string $class) => new $class($this))),
            Parameter::typed(Crawler::class, Parameter::factory(fn() => $this->client()->getCrawler())),
            Parameter::typed(CookieJar::class, Parameter::factory(fn() => $this->client()->getCookieJar())),
            Parameter::typed(AbstractBrowser::class, Parameter::factory(fn() => $this->client())),
        ];
    }
}

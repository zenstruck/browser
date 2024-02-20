<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck;

use Psr\Container\ContainerInterface;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Filesystem\Filesystem;
use Zenstruck\Browser\Assertion\SameUrlAssertion;
use Zenstruck\Browser\Component;
use Zenstruck\Browser\Session;
use Zenstruck\Callback\Parameter;
use Zenstruck\Dom\Exception\RuntimeException;
use Zenstruck\Dom\Node\Form\Field;
use Zenstruck\Dom\Node\Form\Field\Checkbox;
use Zenstruck\Dom\Node\Form\Field\File;
use Zenstruck\Dom\Node\Form\Field\Input;
use Zenstruck\Dom\Node\Form\Field\Radio;
use Zenstruck\Dom\Node\Form\Field\Select\Combobox;
use Zenstruck\Dom\Node\Form\Field\Select\Multiselect;
use Zenstruck\Dom\Node\Form\Field\Textarea;
use Zenstruck\Dom\Selector;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @phpstan-import-type SelectorType from Selector
 * @phpstan-import-type PartsToMatch from SameUrlAssertion
 */
abstract class Browser
{
    private ?string $sourceDir;
    private bool $sourceDebug;
    private Dom $dom;

    /** @var string[] */
    private array $savedSources = [];

    /**
     * @internal
     *
     * @param array<string,mixed> $options
     */
    public function __construct(private Session $session, array $options = [])
    {
        $this->sourceDir = $options['source_dir'] ?? null;
        $this->sourceDebug = $options['source_debug'] ?? false;
    }

    final public function client(): AbstractBrowser
    {
        return $this->session->client();
    }

    final public function dom(): Dom
    {
        if (isset($this->dom)) {
            return $this->dom;
        }

        $dom = $this->session->dom();

        if (!$exceptionClassNode = $dom->find('.trace-details .trace-class')) {
            return $this->dom = $dom;
        }

        $messageNode = $dom->find('.exception-message-wrapper .exception-message');

        Assert::fail('The last request threw an exception: %s - %s', [
            \preg_replace('/\s+/', '', $exceptionClassNode->text()),
            $messageNode?->text() ?? 'unknown message',
        ]);
    }

    /**
     * @return static
     */
    final public function visit(string $uri): self
    {
        return $this->wrapRequest(fn() => $this->client()->request('GET', $uri));
    }

    /**
     * @param PartsToMatch $parts The url parts to check {@see parse_url} (use empty array for "all")
     *
     * @return static
     */
    final public function assertOn(string $expected, array $parts = ['path', 'query', 'fragment']): self
    {
        Assert::run(new SameUrlAssertion($this->session->currentUrl(), $expected, $parts));

        return $this;
    }

    /**
     * @param PartsToMatch $parts The url parts to check {@see parse_url} (use empty array for "all")
     *
     * @return static
     */
    final public function assertNotOn(string $expected, array $parts = ['path', 'query', 'fragment']): self
    {
        Assert::not(new SameUrlAssertion($this->session->currentUrl(), $expected, $parts));

        return $this;
    }

    /**
     * @return static
     */
    final public function assertContains(string $expected): self
    {
        Assert::that($this->content())->contains($expected, strict: false);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertNotContains(string $expected): self
    {
        Assert::that($this->content())->doesNotContain($expected, strict: false);

        return $this;
    }

    final public function crawler(): Crawler
    {
        return $this->client()->getCrawler();
    }

    final public function content(): string
    {
        return $this->session->content();
    }

    /**
     * @return static
     */
    final public function assertSee(string $expected): self
    {
        $this->dom()->assert()->contains($expected);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertNotSee(string $expected): self
    {
        $this->dom()->assert()->doesNotContain($expected);

        return $this;
    }

    /**
     * @param SelectorType $selector
     *
     * @return static
     */
    final public function assertSeeIn(Selector|string|callable $selector, string $expected): self
    {
        $this->dom()->assert()->containsIn($selector, $expected);

        return $this;
    }

    /**
     * @param SelectorType $selector
     *
     * @return static
     */
    final public function assertNotSeeIn(Selector|string|callable $selector, string $expected): self
    {
        $this->dom()->assert()->doesNotContainIn($selector, $expected);

        return $this;
    }

    /**
     * @param SelectorType $selector
     *
     * @return static
     */
    final public function assertSeeElement(Selector|string|callable $selector): self
    {
        $this->dom()->assert()->hasElement($selector);

        return $this;
    }

    /**
     * @param SelectorType $selector
     *
     * @return static
     */
    final public function assertNotSeeElement(Selector|string|callable $selector): self
    {
        $this->dom()->assert()->doesNotHaveElement($selector);

        return $this;
    }

    /**
     * @param SelectorType $selector
     *
     * @return static
     */
    final public function assertElementCount(Selector|string|callable $selector, int $count): self
    {
        $this->dom()->assert()->hasElementCount($selector, $count);

        return $this;
    }

    /**
     * @param SelectorType $selector
     *
     * @return static
     */
    final public function assertElementAttributeContains(Selector|string|callable $selector, string $attribute, string $expected): self
    {
        $this->dom()->assert()->attributeContains($selector, $attribute, $expected);

        return $this;
    }

    /**
     * @param SelectorType $selector
     *
     * @return static
     */
    final public function assertElementAttributeNotContains(Selector|string|callable $selector, string $attribute, string $expected): self
    {
        $this->dom()->assert()->attributeDoesNotContain($selector, $attribute, $expected);

        return $this;
    }

    /**
     * @param SelectorType $selector
     *
     * @return static
     */
    final public function fillField(Selector|string|callable $selector, string $value): self
    {
        $field = $this->field($selector);

        if (!$field instanceof Input && !$field instanceof Textarea) {
            throw new RuntimeException(\sprintf('Node with selector "%s" is not a fillable form field.', Selector::wrap($selector)));
        }

        $field->fill($value);

        return $this;
    }

    /**
     * @param SelectorType $selector
     *
     * @return static
     */
    final public function checkField(Selector|string|callable $selector): self
    {
        $field = $this->field($selector);

        match ($field::class) {
            Radio::class => $field->select(),
            Checkbox::class => $field->check(),
            default => throw new RuntimeException(\sprintf('Node with selector "%s" is not a checkable form field.', Selector::wrap($selector))),
        };

        return $this;
    }

    /**
     * @param SelectorType $selector
     *
     * @return static
     */
    final public function uncheckField(Selector|string|callable $selector): self
    {
        $this->field($selector)->ensure(Checkbox::class)->uncheck();

        return $this;
    }

    /**
     * Select Radio, check checkbox, select single/multiple values.
     *
     * @param SelectorType         $selector
     * @param string|string[]|null $value    null: check radio/checkbox
     *                                       string: single value
     *                                       array: multiple values
     *
     * @return static
     */
    final public function selectField(Selector|string|callable $selector, string|array|null $value = null): self
    {
        $field = $this->field($selector);

        if ($field instanceof Checkbox) {
            $field->check();

            return $this;
        }

        if ($field instanceof Radio && !\is_array($value)) {
            $field->select($value);

            return $this;
        }

        if ($field instanceof Combobox && \is_array($value)) {
            throw new RuntimeException('Combobox does not support multiple values.');
        }

        if ($field instanceof Combobox) {
            $field->select((string) $value);

            return $this;
        }

        $value = (array) $value;
        $field = $field->ensure(Multiselect::class);

        $value ? $field->select($value) : $field->deselectAll();

        return $this;
    }

    /**
     * @param SelectorType $selector
     *
     * @return static
     */
    final public function selectFieldOption(Selector|string|callable $selector, string $value): self
    {
        return $this->selectField($selector, $value);
    }

    /**
     * @param SelectorType $selector
     * @param string[]     $values
     *
     * @return static
     */
    final public function selectFieldOptions(Selector|string|callable $selector, array $values): self
    {
        return $this->selectField($selector, $values);
    }

    /**
     * @param SelectorType    $selector
     * @param string|string[] $filename string: single file
     *                                  array: multiple files
     *
     * @return static
     */
    final public function attachFile(Selector|string|callable $selector, array|string $filename): self
    {
        $this->field($selector)->ensure(File::class)->attach(...(array) $filename);

        return $this;
    }

    /**
     * Click on a button, link or any DOM element.
     *
     * @param SelectorType $selector
     *
     * @return static
     */
    final public function click(Selector|string|callable $selector): self
    {
        $node = $this->dom()->findOrFail(Selector::clickable($selector));

        Assert::true($node->isVisible(), 'Clickable element "%s" is not visible.', [$selector]);

        return $this->wrapRequest(fn() => $node->click());
    }

    /**
     * @param SelectorType $selector
     *
     * @return static
     */
    final public function assertFieldEquals(Selector|string|callable $selector, string $expected): self
    {
        $this->dom()->assert()->fieldEquals($selector, $expected);

        return $this;
    }

    /**
     * @param SelectorType $selector
     *
     * @return static
     */
    final public function assertFieldNotEquals(Selector|string|callable $selector, string $expected): self
    {
        $this->dom()->assert()->fieldDoesNotEqual($selector, $expected);

        return $this;
    }

    /**
     * @param SelectorType $selector
     *
     * @return static
     */
    final public function assertSelected(Selector|string|callable $selector, string $expected): self
    {
        $this->dom()->assert()->fieldSelected($selector, $expected);

        return $this;
    }

    /**
     * @param SelectorType $selector
     *
     * @return static
     */
    final public function assertNotSelected(Selector|string|callable $selector, string $expected): self
    {
        $this->dom()->assert()->fieldNotSelected($selector, $expected);

        return $this;
    }

    /**
     * @param SelectorType $selector
     *
     * @return static
     */
    final public function assertChecked(Selector|string|callable $selector): self
    {
        $this->dom()->assert()->fieldChecked($selector);

        return $this;
    }

    /**
     * @param SelectorType $selector
     *
     * @return static
     */
    final public function assertNotChecked(Selector|string|callable $selector): self
    {
        $this->dom()->assert()->fieldNotChecked($selector);

        return $this;
    }

    /**
     * @return static
     */
    final public function use(callable $callback): self
    {
        Callback::createFor($callback)->invokeAll(
            Parameter::union(...$this->useParameters()),
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

        (new Filesystem())->dumpFile($this->savedSources[] = $filename, $this->source($this->sourceDebug));

        return $this;
    }

    /**
     * @param SelectorType|null $selector
     *
     * @return static
     */
    abstract public function dump(Selector|string|callable|null $selector = null): self;

    /**
     * @param SelectorType|null $selector
     */
    final public function dd(Selector|string|callable|null $selector = null): void
    {
        $this->dump($selector)->exit();
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

    /**
     * @internal
     *
     * @return static
     */
    protected function wrapRequest(callable $callback): self
    {
        $callback();
        unset($this->dom);

        return $this;
    }

    /**
     * @internal
     */
    protected function exit(): void
    {
        exit(1);
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
            Parameter::typed(Dom::class, Parameter::factory(fn() => $this->dom())),
            Parameter::typed(CookieJar::class, Parameter::factory(fn() => $this->client()->getCookieJar())),
            Parameter::typed(AbstractBrowser::class, Parameter::factory(fn() => $this->client())),
            Parameter::typed(ContainerInterface::class, Parameter::factory(fn() => \method_exists($this->client(), 'getContainer') ? $this->client()->getContainer() : null))->optional(),
        ];
    }

    /**
     * @internal
     */
    abstract protected function source(bool $debug): string;

    private function field(Selector|string|callable $selector): Field
    {
        return $this->dom()->findOrFail(Selector::field($selector))->ensure(Field::class);
    }
}

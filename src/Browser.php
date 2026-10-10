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
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
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
use Zenstruck\Dom\Node\Form\Field\Select;
use Zenstruck\Dom\Node\Form\Field\Select\Combobox;
use Zenstruck\Dom\Node\Form\Field\Select\Multiselect;
use Zenstruck\Dom\Node\Form\Field\Select\Option;
use Zenstruck\Dom\Node\Form\Field\Textarea;
use Zenstruck\Dom\Selector;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Proxy as LegacyProxy;

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

    /** @var array{0:class-string|callable,1:string|null}|null */
    private ?array $expectedException = null;

    private bool $threwExpectedException = false;

    private bool $catchExceptions = true;

    private ?SessionInterface $httpSession = null;

    /**
     * @internal
     *
     * @param array<string,mixed> $options
     */
    public function __construct(private Session $session, array $options = [])
    {
        $this->sourceDir = $options['source_dir'] ?? null;
        $this->sourceDebug = $options['source_debug'] ?? false;
        $this->catchExceptions = (bool) ($options['catch_exceptions'] ?? true);

        $this->client()->followRedirects((bool) ($options['follow_redirects'] ?? true));
        $this->clientCatchExceptions($this->catchExceptions);
    }

    /**
     * @return AbstractBrowser<Request, Response>
     */
    final public function client(): AbstractBrowser
    {
        return $this->session->client();
    }

    final public function dom(): Dom
    {
        if (isset($this->dom)) {
            return $this->dom;
        }

        $this->ensureResponseUsable();

        $dom = $this->session->dom();

        // an exception page always carries an error status: this runs before every action and
        // assertion, so successful responses are never inspected
        if (!$this->couldBeExceptionPage()) {
            return $this->dom = $dom;
        }

        // Symfony < 7.4 renders an html exception page
        if ($exceptionClassNode = $dom->find('.trace-details .trace-class')) {
            self::failWithException(
                (string) \preg_replace('/\s+/', '', $exceptionClassNode->text()),
                $dom->find('.exception-message-wrapper .exception-message')?->text() ?? 'unknown message',
            );
        }

        // 7.4+ dumps the exception with var-dumper, which is not html when rendered from the cli.
        // the raw response is what carries it: a real browser wraps plain text in an html document,
        // and the match below is anchored at the start
        $content = \ltrim($this->session->rawContent());

        if (!\preg_match('/^([A-Za-z_\\\\][\w\\\\]*) \{#\d+/', $content, $exception)) {
            return $this->dom = $dom;
        }

        self::failWithException(
            $exception[1],
            \preg_match('/#message: "([^"]*)"/', $content, $message) ? $message[1] : 'unknown message',
        );
    }

    /**
     * @return static
     */
    final public function visit(string $uri): self
    {
        return $this->wrapRequest(fn() => $this->doVisit($uri));
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
        $this->ensureResponseUsable();

        return $this->client()->getCrawler();
    }

    final public function content(): string
    {
        $this->ensureResponseUsable();

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

        $this->session->fill($field, $value);

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

        if (!$field instanceof Radio && !$field instanceof Checkbox) {
            throw new RuntimeException(\sprintf('Node with selector "%s" is not a checkable form field.', Selector::wrap($selector)));
        }

        $this->session->select($field);

        return $this;
    }

    /**
     * @param SelectorType $selector
     *
     * @return static
     */
    final public function uncheckField(Selector|string|callable $selector): self
    {
        $this->session->unselect($this->field($selector)->ensure(Checkbox::class));

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
            $this->session->select($field);

            return $this;
        }

        if ($field instanceof Radio && !\is_array($value)) {
            $this->session->select($value ? self::radio($field, $value) : $field);

            return $this;
        }

        if ($field instanceof Combobox && \is_array($value)) {
            throw new RuntimeException('Combobox does not support multiple values.');
        }

        if ($field instanceof Combobox) {
            $this->session->select(self::option($field, (string) $value));

            return $this;
        }

        $field = $field->ensure(Multiselect::class);

        if (!$value) {
            $this->session->unselect($field);

            return $this;
        }

        foreach ((array) $value as $option) {
            $this->session->select(self::option($field, $option));
        }

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
        $field = $this->field($selector)->ensure(File::class);
        $filenames = \array_map(\strval(...), (array) $filename);

        if (\count($filenames) > 1 && !$field->isMultiple()) {
            throw new \InvalidArgumentException('Cannot attach multiple files to a non-multiple file input.');
        }

        foreach ($filenames as $file) {
            if (!\file_exists($file)) {
                throw new \InvalidArgumentException(\sprintf('File "%s" does not exist.', $file));
            }
        }

        $this->session->attach($field, $filenames);

        return $this;
    }

    /**
     * Click on a button, link or any DOM element.
     *
     * @final
     *
     * @param SelectorType $selector
     *
     * @return static
     */
    public function click(Selector|string|callable $selector): self
    {
        $node = $this->dom()->findOrFail(Selector::clickable($selector));

        Assert::true($node->isVisible(), 'Clickable element "%s" is not visible.', [$selector]);

        return $this->wrapRequest(fn() => $this->session->click($node));
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

        $this->flushHttpSession();

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
     * @return static
     */
    final public function assertStatus(int $expected): self
    {
        $this->ensureResponseUsable();

        Assert::that($this->session->statusCode())
            ->is($expected, 'Current response status code is {actual}, but {expected} expected.')
        ;

        return $this;
    }

    /**
     * @return static
     */
    final public function assertSuccessful(): self
    {
        $this->ensureResponseUsable();

        Assert::true(
            $this->session->isSuccess(),
            'Expected successful status code (2xx) but got {actual}.',
            ['actual' => $this->session->statusCode()],
        );

        return $this;
    }

    /**
     * @return static
     */
    final public function assertHeaderEquals(string $header, ?string $expected): self
    {
        $this->ensureResponseUsable();

        Assert::that($this->session->responseHeader($header))
            ->equals($expected, 'Header "{header}" is "{actual}", but "{expected}" expected.', ['header' => $header])
        ;

        return $this;
    }

    /**
     * @return static
     */
    final public function assertHeaderContains(string $header, string $expected): self
    {
        $this->ensureResponseUsable();

        Assert::that($this->session->responseHeader($header))
            ->isNotNull('Header "{header}" is not present in the response.', ['header' => $header])
            ->contains($expected, 'Header "{header}" value "{haystack}" is expected to contain "{needle}".', [
                'header' => $header,
            ])
        ;

        return $this;
    }

    /**
     * @return static
     */
    final public function assertContentType(string $contentType): self
    {
        return $this->assertHeaderContains('Content-Type', $contentType);
    }

    /**
     * @return static
     */
    final public function interceptRedirects(): self
    {
        $this->client()->followRedirects(false);

        return $this;
    }

    /**
     * @return static
     */
    final public function followRedirects(): self
    {
        $this->client()->followRedirects(true);

        if ($this->session->isStarted() && $this->session->isRedirect()) {
            $this->followRedirect();
        }

        return $this;
    }

    /**
     * @param int $max The maximum number of redirects to follow (defaults to "infinite")
     *
     * @return static
     */
    final public function followRedirect(int $max = \PHP_INT_MAX): self
    {
        for ($i = 0; $i < $max; ++$i) {
            if (!$this->session->isRedirect()) {
                break;
            }

            $this->client()->followRedirect();
        }

        return $this;
    }

    /**
     * @return static
     */
    final public function assertRedirected(): self
    {
        $this->ensureResponseUsable();

        if ($this->client()->isFollowingRedirects()) {
            throw new \RuntimeException('Cannot assert redirected if not intercepting redirects. Call ->interceptRedirects() before making the request.');
        }

        Assert::true($this->session->isRedirect(), 'Expected redirect status code (3xx) but got {actual}.', [
            'actual' => $this->session->statusCode(),
        ]);

        return $this;
    }

    /**
     * @param int $max The maximum number of redirects to follow (defaults to "infinite")
     *
     * @return static
     */
    final public function assertRedirectedTo(string $expected, int $max = \PHP_INT_MAX): self
    {
        $this->assertRedirected();
        $this->followRedirect($max);
        $this->assertOn($expected);

        return $this;
    }

    /**
     * Macro for ->interceptRedirects()->withProfiling()->click().
     *
     * Useful for submitting a form and making assertions on the
     * redirect response.
     *
     * @param SelectorType $selector
     *
     * @return static
     */
    final public function clickAndIntercept(Selector|string|callable $selector): self
    {
        return $this
            ->interceptRedirects()
            ->withProfiling()
            ->click($selector)
        ;
    }

    /**
     * By default, exceptions made during a request are caught and converted
     * to responses by Symfony. This disables this behaviour and actually
     * throws the exception.
     *
     * @return static
     */
    final public function throwExceptions(): self
    {
        $this->catchExceptions = false;
        $this->clientCatchExceptions(false);

        return $this;
    }

    /**
     * Re-enables catching exceptions.
     *
     * @return static
     */
    final public function catchExceptions(): self
    {
        $this->catchExceptions = true;
        $this->clientCatchExceptions(true);

        return $this;
    }

    /**
     * Expect the next request to throw this exception. Fails if not thrown.
     *
     * @param class-string|callable $expectedException string: class name of the expected exception
     *                                                 callable: uses the first argument's type-hint
     *                                                 to determine the expected exception class. When
     *                                                 exception is caught, callable is invoked with
     *                                                 the caught exception
     * @param string|null           $expectedMessage   Assert the caught exception message "contains"
     *                                                 this string
     *
     * @return static
     */
    final public function expectException($expectedException, ?string $expectedMessage = null): self
    {
        $this->expectedException = [$expectedException, $expectedMessage];

        return $this;
    }

    /**
     * Enable profiling for the next request. Not required if profiling is
     * globally enabled.
     *
     * @return static
     */
    final public function withProfiling(): self
    {
        if (!\method_exists($client = $this->client(), 'enableProfiler')) {
            throw new \LogicException(\sprintf('%s() is not supported by "%s".', __METHOD__, $client::class));
        }

        $client->enableProfiler();

        return $this;
    }

    final public function profile(): Profile
    {
        if (!\method_exists($client = $this->client(), 'getProfile')) {
            throw new \LogicException(\sprintf('%s() is not supported by "%s".', __METHOD__, $client::class));
        }

        if (!($profile = $client->getProfile()) instanceof Profile) {
            throw new \RuntimeException('Profiler not enabled for this request. Try calling ->withProfiling() before the request.');
        }

        return $profile;
    }

    /**
     * @param UserInterface $user
     *
     * @return static
     */
    public function actingAs(object $user, ?string $firewall = null): self
    {
        if ($user instanceof Factory) { // @phpstan-ignore-line
            trigger_deprecation('zenstruck/browser', '1.9', 'Passing a Factory to actingAs() is deprecated, pass the created object instead.');
            $user = $user->create(); // @phpstan-ignore-line
        }

        if ($user instanceof LegacyProxy) { // @phpstan-ignore-line
            $user = $user->object(); // @phpstan-ignore-line
        }

        if ($user instanceof Proxy) { // @phpstan-ignore-line
            $user = $user->_real(); // @phpstan-ignore-line
        }

        if (!$user instanceof UserInterface) {
            throw new \LogicException(\sprintf('%s() requires the user be an instance of %s.', __METHOD__, UserInterface::class));
        }

        if (!\method_exists($client = $this->client(), 'loginUser')) {
            throw new \LogicException(\sprintf('%s() is not supported by "%s".', __METHOD__, $client::class));
        }

        $client->loginUser(...\array_filter([$user, $firewall]));

        return $this;
    }

    /**
     * @param string|UserInterface|null $as
     *
     * @return static
     */
    public function assertAuthenticated($as = null): self
    {
        $token = $this->securityToken();

        if (!$token && $this->session->isStarted() && !$this->session->isSuccess()) {
            Assert::fail('The last response was not successful so cannot check authentication.');
        }

        Assert::that($token)
            ->isNotNull('Expected to be authenticated but NOT.')
        ;

        if (!$as) {
            return $this;
        }

        if ($as instanceof Factory) { // @phpstan-ignore-line
            trigger_deprecation('zenstruck/browser', '1.9', 'Passing a Factory to assertAuthenticated() is deprecated, pass the created object instead.');
            $as = $as->create(); // @phpstan-ignore-line
        }

        if ($as instanceof LegacyProxy) { // @phpstan-ignore-line
            $as = $as->object(); // @phpstan-ignore-line
        }

        if ($as instanceof Proxy) { // @phpstan-ignore-line
            $as = $as->_real(); // @phpstan-ignore-line
        }

        if ($as instanceof UserInterface) {
            $as = $as->getUserIdentifier();
        }

        if (!\is_string($as)) {
            throw new \LogicException(\sprintf('%s() requires the "as" user be a string or %s.', __METHOD__, UserInterface::class));
        }

        Assert::that($token?->getUserIdentifier())
            ->is($as, 'Expected to be authenticated as "{expected}" but authenticated as "{actual}".')
        ;

        return $this;
    }

    /**
     * @return static
     */
    public function assertNotAuthenticated(): self
    {
        Assert::that($token = $this->securityToken())
            ->isNull('Expected to NOT be authenticated but authenticated as "{actual}".', [
                'actual' => $token ? $token->getUserIdentifier() : null,
            ])
        ;

        return $this;
    }

    /**
     * @internal
     *
     * @return static
     */
    final protected function wrapRequest(callable $callback): self
    {
        unset($this->dom);

        $this->threwExpectedException = false;

        if (!$this->expectedException) {
            $callback();

            return $this;
        }

        $this->clientCatchExceptions(false);

        try {
            Assert::that($callback)->throws(...$this->expectedException);

            // the request never produced a response, so whatever the browser shows is stale
            $this->threwExpectedException = true;
        } finally {
            // a request the browser has not finished can be handled after this one returns, so
            // leaving catching disabled would throw the same exception again, out of a later call
            $this->clientCatchExceptions($this->catchExceptions);

            $this->expectedException = null;
        }

        return $this;
    }

    /**
     * @internal
     */
    protected function doVisit(string $uri): void
    {
        $this->client()->request('GET', $uri);
    }

    /**
     * @internal
     */
    protected function cookieJar(): CookieJar
    {
        return $this->client()->getCookieJar();
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
            Parameter::typed(CookieJar::class, Parameter::factory(fn() => $this->cookieJar())),
            Parameter::typed(AbstractBrowser::class, Parameter::factory(fn() => $this->client())),
            Parameter::typed(ContainerInterface::class, Parameter::factory(fn() => $this->container()))->optional(),
            Parameter::typed(SessionInterface::class, Parameter::factory(fn() => $this->httpSession())),
            Parameter::typed(DataCollectorInterface::class, Parameter::factory(function(string $class) {
                foreach ($this->profile()->getCollectors() as $collector) {
                    if ($class === $collector::class) {
                        return $collector;
                    }
                }

                Assert::fail('DataCollector %s is not available for this request.', [$class]);
            })),
        ];
    }

    /**
     * @internal
     */
    abstract protected function source(bool $debug): string;

    /**
     * @internal
     */
    final protected function session(): Session
    {
        return $this->session;
    }

    private function clientCatchExceptions(bool $catch): void
    {
        if (!\method_exists($client = $this->client(), 'catchExceptions')) {
            throw new \LogicException(\sprintf('Catching exceptions is not supported by "%s".', $client::class));
        }

        $client->catchExceptions($catch);
    }

    /**
     * The session the next request will use, loaded from the session cookie if there is one.
     *
     * It is saved, and its cookie written, once the {@see use()} callback returns.
     */
    private function httpSession(): SessionInterface
    {
        if (null !== $this->httpSession) {
            return $this->httpSession;
        }

        $container = $this->container();

        if (!$container?->has('session.factory')) {
            throw new \LogicException('Sessions are not available/enabled.');
        }

        $session = $container->get('session.factory')->createSession();

        if (null !== $cookie = $this->cookieJar()->get($session->getName())) {
            $session->setId($cookie->getValue());
        }

        $session->start();

        return $this->httpSession = $session;
    }

    private function flushHttpSession(): void
    {
        if (null === $session = $this->httpSession) {
            return;
        }

        $this->httpSession = null;
        $cookie = $this->cookieJar()->get($session->getName());

        // only reading must not start a session the app would then see
        if (null === $cookie && $session instanceof HttpSession && $session->isEmpty()) {
            return;
        }

        $session->save();

        if ($cookie?->getValue() === $session->getId()) {
            return;
        }

        // no domain: the jar then scopes it to whatever host the browser visits
        $this->cookieJar()->set(new Cookie($session->getName(), $session->getId()));
    }

    private function container(): ?ContainerInterface
    {
        return \method_exists($client = $this->client(), 'getContainer') ? $client->getContainer() : null;
    }

    private function securityToken(): ?TokenInterface
    {
        $container = $this->container();

        if (!$container?->has('security.token_storage')) {
            throw new \LogicException('Security not available/enabled.');
        }

        $storage = $container->get('security.token_storage');

        \assert($storage instanceof TokenStorageInterface);

        return $storage->getToken();
    }

    private function ensureResponseUsable(): void
    {
        if ($this->threwExpectedException) {
            Assert::fail('The last request threw the expected exception: make another request before continuing.');
        }
    }

    private function couldBeExceptionPage(): bool
    {
        try {
            return $this->session->statusCode() >= 400;
        } catch (\Throwable) {
            // the session cannot tell us, so fall through to inspecting the response itself
            return true;
        }
    }

    /**
     * @return never
     */
    private static function failWithException(string $class, string $message): void
    {
        Assert::fail('The last request threw an exception: %s - %s', [$class, $message]);
    }

    private function field(Selector|string|callable $selector): Field
    {
        return $this->dom()->findOrFail(Selector::field($selector))->ensure(Field::class);
    }

    private static function radio(Radio $field, string $value): Radio
    {
        return $field->withValue($value) ?? throw new RuntimeException(\sprintf('Could not find radio with value "%s".', $value));
    }

    private static function option(Select $field, string $value): Option
    {
        return $field->optionMatching($value) ?? throw new RuntimeException(\sprintf('Could not find option with value/text "%s".', $value));
    }
}

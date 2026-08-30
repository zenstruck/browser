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

use Behat\Mink\Element\NodeElement;
use Psr\Container\ContainerInterface;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Zenstruck\Browser\Assertion\SameUrlAssertion;
use Zenstruck\Browser\Component;
use Zenstruck\Browser\Session;
use Zenstruck\Browser\Session\Driver;
use Zenstruck\Callback\Parameter;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Proxy as LegacyProxy;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class Browser
{
    private Session $session;
    private ?string $sourceDir;
    private bool $sourceDebug;

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
        $this->sourceDebug = $options['source_debug'] ?? false;
    }

    /**
     * @return AbstractBrowser<Request, Response>
     */
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
        return $this->session()->crawler();
    }

    final public function content(): string
    {
        return $this->session()->content();
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
    public function assertStatus(int $expected): self
    {
        Assert::that($this->session()->statusCode())
            ->is($expected, 'Current response status code is {actual}, but {expected} expected.')
        ;

        return $this;
    }

    /**
     * @return static
     */
    public function assertSuccessful(): self
    {
        $statusCode = $this->session()->statusCode();

        Assert::true(
            $statusCode >= 200 && $statusCode < 300,
            'Expected successful status code (2xx) but got {actual}.',
            ['actual' => $statusCode],
        );

        return $this;
    }

    /**
     * @return static
     */
    public function assertHeaderEquals(string $header, ?string $expected): self
    {
        if (null === $expected) {
            Assert::that($this->session()->getResponseHeader($header))
                ->isNull('Current response header "{header}" is "{actual}", but was not expected.', [
                    'header' => $header,
                ])
            ;

            return $this;
        }

        $this->session()->assert()->responseHeaderEquals($header, $expected);

        return $this;
    }

    /**
     * @return static
     */
    public function assertHeaderContains(string $header, string $expected): self
    {
        $this->session()->assert()->responseHeaderContains($header, $expected);

        return $this;
    }

    /**
     * @return static
     */
    public function assertContentType(string $contentType): self
    {
        return $this->assertHeaderContains('Content-Type', $contentType);
    }

    /**
     * @return static
     */
    final public function fillField(string $selector, string $value): self
    {
        $this->awaitField($selector);
        $this->session()->page()->fillField($selector, $value);

        return $this;
    }

    /**
     * @return static
     */
    final public function checkField(string $selector): self
    {
        $field = $this->awaitField($selector);

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
        $this->awaitField($selector);
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
        $this->awaitField($selector);
        $this->session()->page()->selectFieldOption($selector, $value);

        return $this;
    }

    /**
     * @return static
     */
    final public function selectFieldOptions(string $selector, array $values): self
    {
        $this->awaitField($selector);

        if (!$values) {
            $this->session->page()->fillField($selector, $values);

            return $this;
        }

        foreach ($values as $value) {
            $this->session()->page()->selectFieldOption($selector, $value, true);
        }

        return $this;
    }

    /**
     * @param string|string[] $filename string: single file
     *                                  array: multiple files
     *
     * @return static
     */
    final public function attachFile(string $selector, array|string $filename): self
    {
        $this->awaitField($selector);

        foreach ((array) $filename as $file) {
            if (!\file_exists($file)) {
                throw new \InvalidArgumentException(\sprintf('File "%s" does not exist.', $file));
            }

            $this->session()->page()->attachFileToField($selector, $file);
        }

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

        (new Filesystem())->dumpFile($this->savedSources[] = $filename, $this->session()->source($this->sourceDebug));

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

    /**
     * @internal
     */
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

        if (!$token && $this->session()->isStarted() && !($this->session()->getStatusCode() >= 200 && $this->session()->getStatusCode() < 300)) {
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

        Assert::that($token->getUserIdentifier())
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

        if ($this->session()->isStarted() && $this->session()->isRedirect()) {
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
            if (!$this->session()->isRedirect()) {
                break;
            }

            $this->client()->followRedirect();
        }

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
     * @return static
     */
    final public function clickAndIntercept(string $selector): self
    {
        return $this
            ->interceptRedirects()
            ->withProfiling()
            ->click($selector)
        ;
    }

    /**
     * @return static
     */
    final public function assertRedirected(): self
    {
        if ($this->client()->isFollowingRedirects()) {
            throw new \RuntimeException('Cannot assert redirected if not intercepting redirects. Call ->interceptRedirects() before making the request.');
        }

        Assert::true($this->session()->isRedirect(), 'Expected redirect status code (3xx) but got {actual}.', [
            'actual' => $this->session()->getStatusCode(),
        ]);

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
     * By default, exceptions made during a request are caught and converted
     * to responses by Symfony. This disables this behaviour and actually
     * throws the exception.
     *
     * @return static
     */
    final public function throwExceptions(): self
    {
        $this->session()->catchExceptions(false);

        return $this;
    }

    /**
     * Re-enables catching exceptions.
     *
     * @return static
     */
    final public function catchExceptions(): self
    {
        $this->session()->catchExceptions(true);

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
    public function expectException($expectedException, ?string $expectedMessage = null): self
    {
        $this->session()->expectException($expectedException, $expectedMessage);

        return $this;
    }

    final protected function getClickableElement(string $selector): NodeElement
    {
        $element = $this->session()->autoWait()->lookup(fn() => $this->findClickable($selector));

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
     */
    protected function cookieJar(): CookieJar
    {
        return $this->client()->getCookieJar();
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
            Parameter::typed(CookieJar::class, Parameter::factory(fn() => $this->cookieJar())),
            Parameter::typed(AbstractBrowser::class, Parameter::factory(fn() => $this->client())),
            Parameter::typed(ContainerInterface::class, Parameter::factory(fn() => $this->container())),
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

    private function container(): ?ContainerInterface
    {
        return \method_exists($this->client(), 'getContainer') ? $this->client()->getContainer() : null;
    }

    private function securityToken(): ?TokenInterface
    {
        $container = $this->container();

        if (!$container?->has('security.token_storage')) {
            throw new \LogicException('Security not available/enabled.');
        }

        return $container->get('security.token_storage')->getToken();
    }

    private function findClickable(string $selector): ?NodeElement
    {
        // try button, then link, then css
        return $this->session()->page()->findButton($selector)
            ?? $this->session()->page()->findLink($selector)
            ?? $this->session()->page()->find('css', $selector);
    }

    private function awaitField(string $selector): ?NodeElement
    {
        return $this->session()->autoWait()->lookup(fn() => $this->session()->page()->findField($selector));
    }
}

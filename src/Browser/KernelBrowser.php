<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser;

use Symfony\Bundle\FrameworkBundle\KernelBrowser as SymfonyKernelBrowser;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Zenstruck\Assert;
use Zenstruck\Browser;
use Zenstruck\Browser\Dom\Selector;
use Zenstruck\Browser\Session\KernelSession;
use Zenstruck\Callback\Parameter;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\Proxy;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @phpstan-import-type SelectorType from Selector
 * @phpstan-import-type Options from HttpOptions
 *
 * @method SymfonyKernelBrowser client()
 */
class KernelBrowser extends Browser
{
    protected ?HttpOptions $defaultHttpOptions = null;

    private KernelSession $session;

    /** @var array{0:class-string|callable,1:string|null}|null */
    private ?array $expectedException = null;

    /**
     * @internal
     */
    final public function __construct(SymfonyKernelBrowser $client, array $options = [])
    {
        $client->followRedirects((bool) ($options['follow_redirects'] ?? true));
        $client->catchExceptions((bool) ($options['catch_exceptions'] ?? true));

        parent::__construct($this->session = new KernelSession($client), $options);
    }

    /**
     * @see SymfonyKernelBrowser::disableReboot()
     *
     * @return static
     */
    final public function disableReboot(): self
    {
        $this->client()->disableReboot();

        return $this;
    }

    /**
     * @see SymfonyKernelBrowser::enableReboot()
     *
     * @return static
     */
    final public function enableReboot(): self
    {
        $this->client()->enableReboot();

        return $this;
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
        $this->client()->catchExceptions(false);

        return $this;
    }

    /**
     * Re-enables catching exceptions.
     *
     * @return static
     */
    final public function catchExceptions(): self
    {
        $this->client()->catchExceptions(true);

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
     */
    public function expectException($expectedException, ?string $expectedMessage = null): self
    {
        $this->expectedException = [$expectedException, $expectedMessage];

        return $this->throwExceptions();
    }

    /**
     * Enable profiling for the next request. Not required if profiling is
     * globally enabled.
     *
     * @return static
     */
    final public function withProfiling(): self
    {
        $this->client()->enableProfiler();

        return $this;
    }

    /**
     * @param UserInterface|Proxy<UserInterface>|Factory<UserInterface> $user
     *
     * @return static
     */
    public function actingAs(object $user, ?string $firewall = null): self
    {
        if ($user instanceof Factory) {
            $user = $user->create();
        }

        if ($user instanceof Proxy) {
            $user = $user->object();
        }

        if (!$user instanceof UserInterface) {
            throw new \LogicException(\sprintf('%s() requires the user be an instance of %s.', __METHOD__, UserInterface::class));
        }

        $this->client()->loginUser(...\array_filter([$user, $firewall]));

        return $this;
    }

    /**
     * @param string|UserInterface|Proxy<UserInterface>|Factory<UserInterface>|null $as
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

        if ($as instanceof Factory) {
            $as = $as->create();
        }

        if ($as instanceof Proxy) {
            $as = $as->object();
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

    final public function profile(): Profile
    {
        if (!$profile = $this->client()->getProfile()) {
            throw new \RuntimeException('Profiler not enabled for this request. Try calling ->withProfiling() before the request.');
        }

        return $profile;
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
     * @param HttpOptions|Options $options
     *
     * @return static
     */
    final public function setDefaultHttpOptions(array|HttpOptions $options): self
    {
        $this->defaultHttpOptions = HttpOptions::create($options);

        return $this;
    }

    /**
     * @param HttpOptions|Options $options
     *
     * @return static
     */
    final public function request(string $method, string $url, array|HttpOptions $options = []): self
    {
        if ($this->defaultHttpOptions) {
            $options = $this->defaultHttpOptions->merge($options);
        }

        $options = HttpOptions::create($options);

        $this->wrapRequest(fn() => $this->client()->request(
            $method,
            $options->addQueryToUrl($url),
            $options->parameters(),
            $options->files(),
            $options->server(),
            $options->body(),
        ));

        return $this;
    }

    /**
     * @see request()
     *
     * @param HttpOptions|Options $options
     *
     * @return static
     */
    final public function get(string $url, array|HttpOptions $options = []): self
    {
        return $this->request('GET', $url, $options);
    }

    /**
     * @see request()
     *
     * @param HttpOptions|Options $options
     *
     * @return static
     */
    final public function post(string $url, array|HttpOptions $options = []): self
    {
        return $this->request('POST', $url, $options);
    }

    /**
     * @see request()
     *
     * @param HttpOptions|Options $options
     *
     * @return static
     */
    final public function put(string $url, array|HttpOptions $options = []): self
    {
        return $this->request('PUT', $url, $options);
    }

    /**
     * @see request()
     *
     * @param HttpOptions|Options $options
     *
     * @return static
     */
    final public function delete(string $url, array|HttpOptions $options = []): self
    {
        return $this->request('DELETE', $url, $options);
    }

    /**
     * @see request()
     *
     * @param HttpOptions|Options $options
     *
     * @return static
     */
    final public function patch(string $url, array|HttpOptions $options = []): self
    {
        return $this->request('PATCH', $url, $options);
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
     * @return static
     */
    final public function assertStatus(int $expected): self
    {
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
    final public function assertRedirected(): self
    {
        if ($this->client()->isFollowingRedirects()) {
            throw new \RuntimeException('Cannot assert redirected if not intercepting redirects. Call ->interceptRedirects() before making the request.');
        }

        Assert::true($this->session->isRedirect(), 'Expected redirect status code (3xx) but got {actual}.', [
            'actual' => $this->session->statusCode(),
        ]);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertHeaderEquals(string $header, ?string $expected): self
    {
        Assert::that($this->client()->getResponse()->headers->get($header))
            ->equals($expected, 'Header "{header}" is "{actual}", but "{expected}" expected.', ['header' => $header])
        ;

        return $this;
    }

    /**
     * @return static
     */
    final public function assertHeaderContains(string $header, string $expected): self
    {
        Assert::that($this->client()->getResponse()->headers->get($header))
            ->isNotNull('Header "{header}" is not present in the response.', ['header' => $header])
            ->contains($expected, 'Header "{header}" is "{actual}", but "{expected}" expected.', ['header' => $header])
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
    final public function assertJson(): self
    {
        return $this->assertContentType('json');
    }

    /**
     * @return static
     */
    final public function assertXml(): self
    {
        return $this->assertContentType('xml');
    }

    /**
     * @return static
     */
    final public function assertHtml(): self
    {
        return $this->assertContentType('html');
    }

    /**
     * @param string $expression JMESPath expression
     * @param mixed  $expected
     *
     * @return static
     */
    final public function assertJsonMatches(string $expression, $expected): self
    {
        $this->json()->assertMatches($expression, $expected);

        return $this;
    }

    final public function json(): Json
    {
        return new Json($this->assertJson()->content());
    }

    final public function dump(Selector|string|callable|null $selector = null): self
    {
        if (!$selector) {
            Dumper::dump($this->source(true));

            return $this;
        }

        $contentType = $this->normalizedContentType();

        match (true) {
            'json' === $contentType && \is_string($selector) => $this->json()->dump($selector),
            'dom' === $contentType => $this->dom()->dump($selector),
            default => $this->dump(),
        };

        return $this;
    }

    /**
     * @internal
     *
     * @return static
     */
    final protected function wrapRequest(callable $callback): self
    {
        if (!$this->expectedException) {
            return parent::wrapRequest($callback);
        }

        Assert::that($callback)->throws(...$this->expectedException);

        $this->expectedException = null;

        return $this;
    }

    /**
     * @internal
     */
    final protected function source(bool $debug): string
    {
        $ret = '';
        $contentType = $this->normalizedContentType();

        // We never want to prepend non-text files with metadata.
        if ($debug && $contentType) {
            $ret .= "<!--\n";
            $ret .= "URL: {$this->session->currentUrl()} ({$this->session->statusCode()})\n\n";

            foreach ($this->client()->getInternalResponse()->getHeaders() as $header => $values) {
                foreach ((array) $values as $value) {
                    $ret .= "{$header}: {$value}\n";
                }
            }

            $ret .= "-->\n";
        }

        return $ret.('json' === $contentType ? $this->json() : $this->content());
    }

    protected function useParameters(): array
    {
        return [
            ...parent::useParameters(),
            Parameter::typed(Json::class, Parameter::factory(fn() => $this->json())),
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

    private function securityToken(): ?TokenInterface
    {
        $container = $this->client()->getContainer();

        if (!$container->has('security.token_storage')) {
            throw new \LogicException('Security not available/enabled.');
        }

        $storage = $container->get('security.token_storage');

        \assert($storage instanceof TokenStorageInterface);

        return $storage->getToken();
    }

    /**
     * @return "dom"|"json"|"text"|null
     */
    private function normalizedContentType(): ?string
    {
        $contentType = (string) $this->client()->getInternalResponse()->getHeader('Content-Type'); // @phpstan-ignore-line

        return match (true) {
            \str_contains($contentType, 'json') => 'json',
            \str_contains($contentType, 'html') => 'dom',
            \str_contains($contentType, 'xml') => 'dom',
            \str_contains($contentType, 'text') => 'text',
            default => null,
        };
    }
}

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
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Zenstruck\Assert;
use Zenstruck\Browser;
use Zenstruck\Browser\Session\Driver\BrowserKitDriver;
use Zenstruck\Callback\Parameter;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\Proxy;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @phpstan-import-type Options from HttpOptions
 *
 * @method SymfonyKernelBrowser client()
 */
class KernelBrowser extends Browser
{
    private ?HttpOptions $defaultHttpOptions = null;

    /**
     * @internal
     */
    final public function __construct(SymfonyKernelBrowser $client, array $options = [])
    {
        $client->followRedirects((bool) ($options['follow_redirects'] ?? true));
        $client->catchExceptions((bool) ($options['catch_exceptions'] ?? true));

        parent::__construct(new BrowserKitDriver($client), $options);
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
        $this->session()->expectException($expectedException, $expectedMessage);

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

        if (!$token && $this->session()->isStarted() && !($this->session()->getStatusCode() >= 200 && $this->session()->getStatusCode() < 300)) {
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
     * @param HttpOptions|Options $options
     *
     * @return static
     */
    final public function setDefaultHttpOptions($options): self
    {
        $this->defaultHttpOptions = HttpOptions::create($options);

        return $this;
    }

    /**
     * @param HttpOptions|array $options HttpOptions::DEFAULT_OPTIONS
     *
     * @return static
     */
    final public function request(string $method, string $url, $options = []): self
    {
        if ($this->defaultHttpOptions) {
            $options = $this->defaultHttpOptions->merge($options);
        }

        $options = HttpOptions::create($options);

        $this->session()->request($method, $options->addQueryToUrl($url), $options);

        return $this;
    }

    /**
     * @see request()
     *
     * @param HttpOptions|array $options
     *
     * @return static
     */
    public function get(string $url, $options = []): self
    {
        return $this->request('GET', $url, $options);
    }

    /**
     * @see request()
     *
     * @param HttpOptions|array $options
     *
     * @return static
     */
    public function post(string $url, $options = []): self
    {
        return $this->request('POST', $url, $options);
    }

    /**
     * @see request()
     *
     * @param HttpOptions|array $options
     *
     * @return static
     */
    public function put(string $url, $options = []): self
    {
        return $this->request('PUT', $url, $options);
    }

    /**
     * @see request()
     *
     * @param HttpOptions|array $options
     *
     * @return static
     */
    public function delete(string $url, $options = []): self
    {
        return $this->request('DELETE', $url, $options);
    }

    /**
     * @see request()
     *
     * @param HttpOptions|array $options
     *
     * @return static
     */
    public function patch(string $url, $options = []): self
    {
        return $this->request('PATCH', $url, $options);
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
    final public function assertStatus(int $expected): self
    {
        Assert::that($this->session()->getStatusCode())
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
            $this->session()->getStatusCode() >= 200 && $this->session()->getStatusCode() < 300,
            'Expected successful status code (2xx) but got {actual}.',
            ['actual' => $this->session()->getStatusCode()]
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

        Assert::true($this->session()->isRedirect(), 'Expected redirect status code (3xx) but got {actual}.', [
            'actual' => $this->session()->getStatusCode(),
        ]);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertHeaderEquals(string $header, ?string $expected): self
    {
        $this->session()->assert()->responseHeaderEquals($header, $expected);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertHeaderContains(string $header, string $expected): self
    {
        $this->session()->assert()->responseHeaderContains($header, $expected);

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
        return $this->assertJson()->session()->json();
    }

    protected function useParameters(): array
    {
        return [
            ...parent::useParameters(),
            Parameter::typed(Json::class, Parameter::factory(fn() => $this->json())),
            Parameter::typed(DataCollectorInterface::class, Parameter::factory(function(string $class) {
                foreach ($this->profile()->getCollectors() as $collector) {
                    if ($class === \get_class($collector)) {
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

        return $container->get('security.token_storage')->getToken();
    }
}

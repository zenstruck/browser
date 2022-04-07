<?php

namespace Zenstruck\Browser;

use Symfony\Bundle\FrameworkBundle\KernelBrowser as SymfonyKernelBrowser;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpKernel\Profiler\Profile;
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
     * @param object|UserInterface|Proxy|Factory $user
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
     * @param HttpOptions|array $options
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
    final public function get(string $url, $options = []): self
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
    final public function post(string $url, $options = []): self
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
    final public function put(string $url, $options = []): self
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
    final public function delete(string $url, $options = []): self
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
    final public function patch(string $url, $options = []): self
    {
        return $this->request('PATCH', $url, $options);
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
    final public function assertHeaderEquals(string $header, string $expected): self
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
    final public function assertJson(): self
    {
        return $this->assertHeaderContains('Content-Type', 'json');
    }

    /**
     * @return static
     */
    final public function assertXml(): self
    {
        return $this->assertHeaderContains('Content-Type', 'xml');
    }

    /**
     * @return static
     */
    final public function assertHtml(): self
    {
        return $this->assertHeaderContains('Content-Type', 'html');
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
}

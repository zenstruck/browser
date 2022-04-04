<?php

namespace Zenstruck;

use Behat\Mink\Driver\DriverInterface;
use Behat\Mink\Element\DocumentElement;
use Behat\Mink\Mink;
use Behat\Mink\Session;
use Behat\Mink\WebAssert;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\BrowserKit\CookieJar;
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
abstract class Browser
{
    private const SESSION = 'app';

    private AbstractBrowser $client;
    private Mink $mink;
    private ?string $sourceDir = null;

    /** @var string[] */
    private array $savedSources = [];

    /**
     * @internal
     */
    public function __construct(AbstractBrowser $client, DriverInterface $driver)
    {
        $this->client = $client;
        $this->mink = new Mink([self::SESSION => new Session($driver)]);
    }

    final public function client(): AbstractBrowser
    {
        return $this->client;
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
     * @internal
     */
    public function dumpCurrentState(string $filename): void
    {
        $this->saveSource("{$filename}.txt");
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
            Parameter::typed(Response::class, Parameter::factory(fn() => $this->response())),
            Parameter::typed(Crawler::class, Parameter::factory(fn() => $this->client->getCrawler())),
            Parameter::typed(CookieJar::class, Parameter::factory(fn() => $this->client->getCookieJar())),
        ];
    }
}

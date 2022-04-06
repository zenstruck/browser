<?php

namespace Zenstruck;

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
    private ?string $sourceDir = null;

    /** @var string[] */
    private array $savedSources = [];

    /**
     * @internal
     */
    public function __construct(Driver $driver)
    {
        $this->session = new Session($driver);
    }

    final public function client(): AbstractBrowser
    {
        return $this->session->client();
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
        $this->session()->dd($selector);
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

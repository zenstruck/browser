<?php

namespace Zenstruck;

use Behat\Mink\Driver\BrowserKitDriver;
use Behat\Mink\Element\DocumentElement;
use Behat\Mink\Mink;
use Behat\Mink\Session;
use Behat\Mink\WebAssert;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\HttpKernel\HttpKernelBrowser;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\Panther\Client;
use Zenstruck\Browser\Actions;
use Zenstruck\Browser\Assertions;
use Zenstruck\Browser\Mink\PantherBrowserKitDriver;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class Browser
{
    use Actions, Assertions;

    private const SESSION = 'app';

    private AbstractBrowser $inner;
    private Mink $mink;

    final public function __construct(AbstractBrowser $inner)
    {
        $driver = $inner instanceof Client ? new PantherBrowserKitDriver($inner) : new BrowserKitDriver($inner);

        $this->inner = $inner;
        $this->mink = new Mink([self::SESSION => new Session($driver)]);
    }

    final public function inner(): AbstractBrowser
    {
        return $this->inner;
    }

    final public function minkSession(): Session
    {
        return $this->mink->getSession(self::SESSION);
    }

    final public function webAssert(): WebAssert
    {
        return $this->mink->assertSession(self::SESSION);
    }

    final public function documentElement(): DocumentElement
    {
        return $this->minkSession()->getPage();
    }

    final public function interceptRedirects(): self
    {
        $this->inner->followRedirects(false);

        return $this;
    }

    final public function throwExceptions(): self
    {
        if (!$this->inner instanceof HttpKernelBrowser) {
            throw new \RuntimeException('Can only disable exception catching when using HttpKernelBrowser.');
        }

        $this->inner->catchExceptions(false);

        return $this;
    }

    final public function withProfiling(): self
    {
        if (!$this->inner instanceof KernelBrowser) {
            throw new \RuntimeException('KernelBrowser not being used.');
        }

        $this->inner->enableProfiler();

        return $this;
    }

    final public function profile(): Profile
    {
        if (!$this->inner instanceof KernelBrowser) {
            throw new \RuntimeException('KernelBrowser not being used.');
        }

        if (!$profile = $this->inner->getProfile()) {
            throw new \RuntimeException('Profiler not enabled.');
        }

        return $profile;
    }

    final public function with(callable $callback): self
    {
        $callback($this);

        return $this;
    }

    final public function dump(?string $selector = null): self
    {
        $context = 'URL: '.$this->minkSession()->getCurrentUrl().', STATUS: '.$this->minkSession()->getStatusCode();

        dump($context);
        dump($selector ? $this->documentElement()->find('css', $selector)->getText() : $this->documentElement()->getContent());
        dump($context);

        return $this;
    }

    final public function dd(?string $selector = null): void
    {
        $this->dump($selector);
        exit(1);
    }
}

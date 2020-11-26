<?php

namespace Zenstruck;

use Behat\Mink\Driver\BrowserKitDriver;
use Behat\Mink\Element\DocumentElement;
use Behat\Mink\Mink;
use Behat\Mink\Session;
use Behat\Mink\WebAssert;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\HttpKernel\HttpKernelBrowser;
use Zenstruck\Browser\Actions;
use Zenstruck\Browser\Assertions;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class Browser
{
    use Actions, Assertions;

    private const SESSION = 'app';

    private AbstractBrowser $browser;
    private Mink $mink;

    final public function __construct(AbstractBrowser $browser)
    {
        $this->browser = $browser;
        $this->mink = new Mink([self::SESSION => new Session(new BrowserKitDriver($this->browser))]);
    }

    final public function browser(): AbstractBrowser
    {
        return $this->browser;
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
        $this->browser()->followRedirects(false);

        return $this;
    }

    final public function throwExceptions(): self
    {
        if (!$this->browser instanceof HttpKernelBrowser) {
            throw new \RuntimeException('Can only disable exception catching when using HttpKernelBrowser.');
        }

        $this->browser->catchExceptions(false);

        return $this;
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
        dump($selector ? $this->documentElement()->find('css', $selector)->getText() : $this->documentElement()->getText());
        dump($context);

        return $this;
    }

    final public function dd(?string $selector = null): void
    {
        $this->dump($selector);
        exit(1);
    }
}

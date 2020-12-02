<?php

namespace Zenstruck;

use Behat\Mink\Driver\BrowserKitDriver;
use Behat\Mink\Element\DocumentElement;
use Behat\Mink\Mink;
use Behat\Mink\Session;
use Behat\Mink\WebAssert;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Panther\Client;
use Zenstruck\Browser\Actions;
use Zenstruck\Browser\Assertions;
use Zenstruck\Browser\Component;
use Zenstruck\Browser\Mink\PantherBrowserKitDriver;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class Browser implements ContainerAwareInterface
{
    use Actions, Assertions;

    private const SESSION = 'app';

    private AbstractBrowser $inner;
    private Mink $mink;
    private ?ContainerInterface $container = null;

    public function __construct(AbstractBrowser $inner)
    {
        $driver = $inner instanceof Client ? new PantherBrowserKitDriver($inner) : new BrowserKitDriver($inner);

        $this->inner = $inner;
        $this->mink = new Mink([self::SESSION => new Session($driver)]);
    }

    final public function setContainer(?ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    final public function container(): ContainerInterface
    {
        if (!$this->container) {
            throw new \RuntimeException('Container has not been set.');
        }

        return $this->container;
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

    final public function with(callable $callback): self
    {
        $parameters = \array_map(
            function(\ReflectionParameter $parameter) {
                $type = $parameter->getType();

                if (!$type || ($type instanceof \ReflectionNamedType && \is_a($type->getName(), self::class, true))) {
                    return $this;
                }

                if (!$type instanceof \ReflectionNamedType || !\is_a($type->getName(), Component::class, true)) {
                    throw new \TypeError('Browser::with() callback can only take instances of Browser and/or Component as parameters.');
                }

                $class = $type->getName();

                return new $class($this);
            },
            (new \ReflectionFunction(\Closure::fromCallable($callback)))->getParameters()
        );

        $callback(...$parameters);

        return $this;
    }

    final public function dump(?string $selector = null): self
    {
        $context = 'URL: '.$this->minkSession()->getCurrentUrl().', STATUS: '.$this->inner()->getInternalResponse()->getStatusCode();

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

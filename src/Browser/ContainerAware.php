<?php

namespace Zenstruck\Browser;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait ContainerAware
{
    private ?ContainerInterface $container = null;

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
}

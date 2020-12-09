<?php

namespace Zenstruck\Browser\Test;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Zenstruck\Browser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait HasBrowser
{
    final protected function browser(): Browser
    {
        $browser = $this->createBrowser();

        if (!$this instanceof KernelTestCase) {
            return $browser;
        }

        if (!static::$booted) {
            static::bootKernel();
        }

        if ($browser instanceof ContainerAwareInterface) {
            $browser->setContainer(static::$container);
        }

        return $browser;
    }

    abstract protected function createBrowser(): Browser;
}

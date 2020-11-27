<?php

namespace Zenstruck\Browser\Test;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Browser as DefaultBrowser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @mixin KernelTestCase
 */
trait HasBrowser
{
    final protected function browser(): DefaultBrowser
    {
        if (!$this instanceof KernelTestCase) {
            throw new \RuntimeException(\sprintf('The "%s" trait can only be used on TestCases that extend "%s".', __TRAIT__, KernelTestCase::class));
        }

        // reboot kernel before starting browser
        static::bootKernel();

        return $this->createBrowser();
    }

    protected function createBrowser(): DefaultBrowser
    {
        return new DefaultBrowser(static::$container->get('test.client'));
    }
}

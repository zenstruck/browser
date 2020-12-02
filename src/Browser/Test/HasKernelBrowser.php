<?php

namespace Zenstruck\Browser\Test;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\KernelBrowser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @method KernelBrowser browser()
 */
trait HasKernelBrowser
{
    use HasBrowser;

    protected function createBrowser(): KernelBrowser
    {
        if (!$this instanceof KernelTestCase) {
            throw new \RuntimeException(\sprintf('The "%s" trait can only be used on TestCases that extend "%s".', __TRAIT__, KernelTestCase::class));
        }

        $class = static::kernelBrowserClass();

        if (!\is_a($class, KernelBrowser::class, true)) {
            throw new \RuntimeException(\sprintf('"KERNEL_BROWSER_CLASS" env variable must reference a class that extends %s.', KernelBrowser::class));
        }

        if ($this instanceof WebTestCase) {
            static::ensureKernelShutdown();

            return new $class(static::createClient());
        }

        // reboot kernel before starting browser
        static::bootKernel();

        if (!static::$container->has('test.client')) {
            throw new \RuntimeException('The Symfony test client is not enabled.');
        }

        return new $class(static::$container->get('test.client'));
    }

    protected static function kernelBrowserClass(): string
    {
        return $_SERVER['KERNEL_BROWSER_CLASS'] ?? KernelBrowser::class;
    }
}

<?php

namespace Zenstruck\Browser\Test;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\KernelBrowser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait HasBrowser
{
    public function browser(array $options = []): KernelBrowser
    {
        if (!$this instanceof KernelTestCase) {
            throw new \RuntimeException(\sprintf('The "%s" method can only be used on TestCases that extend "%s".', __METHOD__, KernelTestCase::class));
        }

        $class = $_SERVER['KERNEL_BROWSER_CLASS'] ?? KernelBrowser::class;

        if (!\is_a($class, KernelBrowser::class, true)) {
            throw new \RuntimeException(\sprintf('"KERNEL_BROWSER_CLASS" env variable must reference a class that extends %s.', KernelBrowser::class));
        }

        if ($this instanceof WebTestCase) {
            static::ensureKernelShutdown();

            $browser = new $class(static::createClient($options));
        } else {
            // reboot kernel before starting browser
            static::bootKernel($options);

            if (!static::$container->has('test.client')) {
                throw new \RuntimeException('The Symfony test client is not enabled.');
            }

            $browser = new $class(static::$container->get('test.client'));
        }

        BrowserExtension::registerBrowser($browser);

        return $browser
            ->setSourceDir($_SERVER['BROWSER_SOURCE_DIR'] ?? './var/browser/source')
        ;
    }
}

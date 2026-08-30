<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Test;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\KernelBrowser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait HasBrowser
{
    /**
     * @see WebTestCase::createClient()
     */
    protected function browser(array $options = [], array $server = []): KernelBrowser
    {
        if (!$this instanceof KernelTestCase) {
            throw new \LogicException(\sprintf('A KernelBrowser can only be created in TestCases that extend "%s".', KernelTestCase::class));
        }

        $class = $_SERVER['KERNEL_BROWSER_CLASS'] ?? KernelBrowser::class;

        if (!\is_a($class, KernelBrowser::class, true)) {
            throw new \LogicException(\sprintf('"KERNEL_BROWSER_CLASS" env variable must reference a class that extends %s.', KernelBrowser::class));
        }

        $browserOptions = [
            'source_dir' => $_SERVER['BROWSER_SOURCE_DIR'] ?? './var/browser/source',
            'source_debug' => $_SERVER['BROWSER_SOURCE_DEBUG'] ?? false,
            'follow_redirects' => (bool) ($_SERVER['BROWSER_FOLLOW_REDIRECTS'] ?? true),
            'catch_exceptions' => (bool) ($_SERVER['BROWSER_CATCH_EXCEPTIONS'] ?? true),
        ];

        if ($this instanceof WebTestCase) {
            static::ensureKernelShutdown();

            $browser = new $class(static::createClient($options, $server), $browserOptions); // @phpstan-ignore staticMethod.notFound
        } else {
            // reboot kernel before starting browser
            static::bootKernel($options);

            if (!static::getContainer()->has('test.client')) {
                throw new \RuntimeException('The Symfony test client is not enabled.');
            }

            $client = static::getContainer()->get('test.client');
            $client->setServerParameters($server);

            $browser = new $class($client, $browserOptions);
        }

        BrowserExtension::registerBrowser($browser);

        return $browser;
    }
}

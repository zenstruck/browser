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

use PHPUnit\Framework\Attributes\After;
use Symfony\Bundle\FrameworkBundle\KernelBrowser as SymfonyKernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Panther\Client as PantherClient;
use Symfony\Component\Panther\PantherTestCase;
use Symfony\Component\Panther\PantherTestCaseTrait;
use Zenstruck\Browser\BrowserFactory;
use Zenstruck\Browser\BrowserOptions;
use Zenstruck\Browser\BrowserRegistry;
use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Browser\PantherBrowser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait HasBrowser
{
    private static ?PhpUnitKernelBooter $browserKernelBooter = null;

    /**
     * @internal
     *
     * @after
     */
    #[After]
    final public static function _resetBrowserClients(): void
    {
        self::$browserKernelBooter?->reset();
        self::$browserKernelBooter = null;
    }

    /**
     * @see PantherTestCase::createPantherClient()
     */
    protected function pantherBrowser(array $options = [], array $kernelOptions = [], array $managerOptions = []): PantherBrowser
    {
        if (!\class_exists(PantherClient::class)) {
            throw new \LogicException('symfony/panther must be installed to use the PantherBrowser (composer require symfony/panther).');
        }

        if (!\method_exists(static::class, 'createPantherClient')) {
            throw new \LogicException(\sprintf('A PantherBrowser can only be created in TestCases that extend "%s" or use "%s".', PantherTestCase::class, PantherTestCaseTrait::class));
        }

        return $this->browserFactory()->createPantherBrowser($options, $kernelOptions, $managerOptions);
    }

    /**
     * @see WebTestCase::createClient()
     */
    protected function browser(array $options = [], array $server = []): KernelBrowser
    {
        if (!$this instanceof KernelTestCase) {
            throw new \LogicException(\sprintf('A KernelBrowser can only be created in TestCases that extend "%s".', KernelTestCase::class));
        }

        return $this->browserFactory()->createKernelBrowser($options, $server);
    }

    private function browserFactory(): BrowserFactory
    {
        $booter = self::$browserKernelBooter ??= new PhpUnitKernelBooter(
            createKernelBrowserClient: function (array $options, array $server): SymfonyKernelBrowser {
                if ($this instanceof WebTestCase) {
                    static::ensureKernelShutdown();

                    return static::createClient($options, $server); // @phpstan-ignore staticMethod.notFound, return.type
                }

                static::bootKernel($options);

                $container = static::getContainer();

                if (!$container->has('test.client')) {
                    throw new \RuntimeException('The Symfony test client is not enabled.');
                }

                $client = $container->get('test.client');
                \assert($client instanceof SymfonyKernelBrowser);
                $client->setServerParameters($server);

                return $client;
            },
            createPantherClient: \method_exists(static::class, 'createPantherClient')
                ? static fn (array $options, array $kernelOptions, array $managerOptions): PantherClient => static::createPantherClient($options, $kernelOptions, $managerOptions) // @phpstan-ignore staticMethod.notFound
                : null,
            createAdditionalPantherClient: \method_exists(static::class, 'createAdditionalPantherClient')
                ? static fn (): PantherClient => static::createAdditionalPantherClient() // @phpstan-ignore staticMethod.notFound
                : null,
        );

        return new BrowserFactory($booter, BrowserRegistry::default(), BrowserOptions::fromEnv());
    }
}

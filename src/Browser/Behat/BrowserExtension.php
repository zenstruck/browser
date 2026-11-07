<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Behat;

use Behat\MinkExtension\ServiceContainer\MinkExtension;
use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use FriendsOfBehat\SymfonyExtension\ServiceContainer\SymfonyExtension;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Zenstruck\Browser\Behat\Driver\KernelDriverFactory;
use Zenstruck\Browser\Behat\Driver\PlaywrightDriverFactory;

/**
 * @experimental
 */
final class BrowserExtension implements Extension
{
    public function getConfigKey(): string
    {
        return 'zenstruck_browser';
    }

    public function initialize(ExtensionManager $extensionManager): void
    {
        if (!$extensionManager->getExtension('fob_symfony') instanceof SymfonyExtension) {
            throw new \LogicException('BrowserExtension requires FriendsOfBehat\\SymfonyExtension\\ServiceContainer\\SymfonyExtension to be enabled.');
        }

        $mink = $extensionManager->getExtension('mink');

        if (!$mink instanceof MinkExtension) {
            throw new \LogicException('BrowserExtension requires Behat\\MinkExtension\\ServiceContainer\\MinkExtension to be enabled.');
        }

        $mink->registerDriverFactory(new KernelDriverFactory());
        $mink->registerDriverFactory(new PlaywrightDriverFactory());
    }

    public function configure(ArrayNodeDefinition $builder): void
    {
    }

    public function load(ContainerBuilder $container, array $config): void
    {
    }

    public function process(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition(MinkExtension::MINK_ID);
        $definition->setClass(Mink::class);
    }
}

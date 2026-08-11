<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Bridge\Behat;

use Behat\Behat\Context\ServiceContainer\ContextExtension;
use Behat\Testwork\EventDispatcher\ServiceContainer\EventDispatcherExtension;
use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Zenstruck\Browser\Artifact\ArtifactCollector;
use Zenstruck\Browser\Artifact\ArtifactSink;
use Zenstruck\Browser\Bridge\Behat\EventListener\ArtifactListener;
use Zenstruck\Browser\Bridge\Behat\Initializer\BrowserContextInitializer;
use Zenstruck\Browser\Bridge\Behat\Kernel\StandaloneKernelBooter;
use Zenstruck\Browser\Bridge\Behat\Kernel\SymfonyExtensionKernelBooter;
use Zenstruck\Browser\Bridge\Behat\Output\BehatOutputArtifactSink;
use Zenstruck\Browser\BrowserFactory;
use Zenstruck\Browser\BrowserOptions;
use Zenstruck\Browser\BrowserRegistry;
use Zenstruck\Browser\KernelBooter;

/**
 * Behat extension that wires the zenstruck/browser Behat bridge: registers the
 * browser factory, the context initializer, and the artifact-capture listener.
 *
 * Selects the {@see KernelBooter} implementation at compile time: when
 * `friends-of-behat/symfony-extension` is enabled in the same `behat.yml`, the
 * {@see SymfonyExtensionKernelBooter} pulls the kernel from that extension;
 * otherwise the {@see StandaloneKernelBooter} boots the kernel itself from
 * env vars (KERNEL_CLASS, APP_ENV, APP_DEBUG).
 *
 * @author Hugo Hamon <hello@kodero.fr>
 */
final class BrowserExtension implements Extension
{
    private const /*string*/ FOB_SYMFONY_EXTENSION = 'FriendsOfBehat\\SymfonyExtension\\ServiceContainer\\SymfonyExtension';

    public function getConfigKey(): string
    {
        return 'zenstruck_browser';
    }

    public function initialize(ExtensionManager $extensionManager): void
    {
    }

    public function configure(ArrayNodeDefinition $builder): void
    {
        $builder
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('kernel_class')->defaultNull()->end()
                ->scalarNode('env')->defaultValue('test')->end()
                ->booleanNode('debug')->defaultTrue()->end()
                ->scalarNode('kernel_browser_class')->defaultNull()->end()
                ->scalarNode('panther_browser_class')->defaultNull()->end()
                ->scalarNode('source_dir')->defaultValue('./var/browser/source')->end()
                ->booleanNode('source_debug')->defaultFalse()->end()
                ->booleanNode('follow_redirects')->defaultTrue()->end()
                ->booleanNode('catch_exceptions')->defaultTrue()->end()
                ->scalarNode('screenshot_dir')->defaultValue('./var/browser/screenshots')->end()
                ->scalarNode('console_log_dir')->defaultValue('./var/browser/console-logs')->end()
                ->booleanNode('always_start_webserver')->defaultFalse()->end()
                ->scalarNode('panther_browser')->defaultNull()->end()
            ->end()
        ;
    }

    public function load(ContainerBuilder $container, array $config): void
    {
        $container
            ->setDefinition(BrowserRegistry::class, new Definition(BrowserRegistry::class))
            ->setPublic(false)
        ;

        $container
            ->setDefinition(
                BrowserOptions::class,
                new Definition(BrowserOptions::class, [
                    $config['kernel_browser_class'],
                    $config['panther_browser_class'],
                    $config['source_dir'],
                    $config['source_debug'],
                    $config['follow_redirects'],
                    $config['catch_exceptions'],
                    $config['screenshot_dir'],
                    $config['console_log_dir'],
                    $config['always_start_webserver'],
                    $config['panther_browser'],
                ]),
            )
            ->setPublic(false);

        $this->registerKernelBooter($container, $config);

        $container
            ->setDefinition(
                BrowserFactory::class,
                new Definition(BrowserFactory::class, [
                    new Reference(KernelBooter::class),
                    new Reference(BrowserRegistry::class),
                    new Reference(BrowserOptions::class),
                ]),
            )
            ->setPublic(false);

        $container
            ->setDefinition(ArtifactSink::class, new Definition(BehatOutputArtifactSink::class))
            ->setPublic(false);

        $container
            ->setDefinition(
                ArtifactCollector::class,
                new Definition(ArtifactCollector::class, [
                    new Reference(BrowserRegistry::class),
                    new Reference(ArtifactSink::class),
                ]),
            )
            ->setPublic(false);

        $initializer = new Definition(BrowserContextInitializer::class, [
            new Reference(BrowserFactory::class),
            new Reference(BrowserRegistry::class),
            new Reference(KernelBooter::class),
        ]);

        $initializer->addTag(ContextExtension::INITIALIZER_TAG);
        $container->setDefinition(BrowserContextInitializer::class, $initializer);

        $listener = new Definition(ArtifactListener::class, [
            new Reference(ArtifactCollector::class),
            new Reference(KernelBooter::class),
        ]);

        $listener->addTag(EventDispatcherExtension::SUBSCRIBER_TAG);
        $container->setDefinition(ArtifactListener::class, $listener);
    }

    public function process(ContainerBuilder $container): void
    {
    }

    private function registerKernelBooter(ContainerBuilder $container, array $config): void
    {
        if (\class_exists(self::FOB_SYMFONY_EXTENSION)) {
            $container
                ->setDefinition(
                    SymfonyExtensionKernelBooter::class,
                    new Definition(SymfonyExtensionKernelBooter::class, [
                        new Reference('fob_symfony.kernel'),
                    ])
                )
                ->setPublic(false);

            $container->setAlias(KernelBooter::class, SymfonyExtensionKernelBooter::class);

            return;
        }

        $container
            ->setDefinition(
                StandaloneKernelBooter::class,
                new Definition(StandaloneKernelBooter::class, [
                    $config['kernel_class'],
                    $config['env'],
                    $config['debug'],
                ]),
            )
            ->setPublic(false);

        $container->setAlias(KernelBooter::class, StandaloneKernelBooter::class);
    }
}

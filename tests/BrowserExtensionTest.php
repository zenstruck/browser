<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Tests;

use Behat\Behat\Context\ServiceContainer\ContextExtension;
use Behat\Testwork\EventDispatcher\ServiceContainer\EventDispatcherExtension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Zenstruck\Browser\Bridge\Behat\BrowserExtension;
use Zenstruck\Browser\Bridge\Behat\Initializer\BrowserContextInitializer;
use Zenstruck\Browser\Bridge\Behat\EventListener\ArtifactListener;
use Zenstruck\Browser\Bridge\Behat\Kernel\StandaloneKernelBooter;
use Zenstruck\Browser\Bridge\Behat\Kernel\SymfonyExtensionKernelBooter;
use Zenstruck\Browser\Bridge\Behat\Output\BehatOutputArtifactSink;
use Zenstruck\Browser\Artifact\ArtifactCollector;
use Zenstruck\Browser\Artifact\ArtifactSink;
use Zenstruck\Browser\BrowserFactory;
use Zenstruck\Browser\BrowserOptions;
use Zenstruck\Browser\BrowserRegistry;
use Zenstruck\Browser\KernelBooter;

/**
 * @author Hugo Hamon <hello@kodero.fr>
 */
final class BrowserExtensionTest extends TestCase
{
    private const FOB_SYMFONY_EXTENSION = 'FriendsOfBehat\\SymfonyExtension\\ServiceContainer\\SymfonyExtension';

    /**
     * @test
     */
    public function get_config_key(): void
    {
        $this->assertSame('zenstruck_browser', (new BrowserExtension())->getConfigKey());
    }

    /**
     * @test
     */
    public function initialize_does_nothing(): void
    {
        (new BrowserExtension())->initialize(
            new ExtensionManager([]),
        );

        $this->addToAssertionCount(1);
    }

    /**
     * @test
     */
    public function process_does_nothing(): void
    {
        (new BrowserExtension())->process(new ContainerBuilder());

        $this->addToAssertionCount(1);
    }

    /**
     * @test
     */
    public function configure_has_default_values(): void
    {
        $treeBuilder = new TreeBuilder('zenstruck_browser');
        (new BrowserExtension())->configure($treeBuilder->getRootNode());

        $config = (new Processor())->process($treeBuilder->buildTree(), [[]]);

        $this->assertNull($config['kernel_class']);
        $this->assertSame('test', $config['env']);
        $this->assertTrue($config['debug']);
        $this->assertNull($config['kernel_browser_class']);
        $this->assertNull($config['panther_browser_class']);
        $this->assertSame('./var/browser/source', $config['source_dir']);
        $this->assertFalse($config['source_debug']);
        $this->assertTrue($config['follow_redirects']);
        $this->assertTrue($config['catch_exceptions']);
        $this->assertSame('./var/browser/screenshots', $config['screenshot_dir']);
        $this->assertSame('./var/browser/console-logs', $config['console_log_dir']);
        $this->assertFalse($config['always_start_webserver']);
        $this->assertNull($config['panther_browser']);
    }

    /**
     * @test
     */
    public function configure_accepts_custom_values(): void
    {
        $treeBuilder = new TreeBuilder('zenstruck_browser');
        (new BrowserExtension())->configure($treeBuilder->getRootNode());

        $config = (new Processor())->process($treeBuilder->buildTree(), [[
            'kernel_class' => 'App\\CustomKernel',
            'env' => 'staging',
            'debug' => false,
            'source_dir' => '/custom/source',
            'always_start_webserver' => true,
            'panther_browser' => 'firefox',
        ]]);

        $this->assertSame('App\\CustomKernel', $config['kernel_class']);
        $this->assertSame('staging', $config['env']);
        $this->assertFalse($config['debug']);
        $this->assertSame('/custom/source', $config['source_dir']);
        $this->assertTrue($config['always_start_webserver']);
        $this->assertSame('firefox', $config['panther_browser']);
        $this->assertTrue($config['follow_redirects']);
    }

    /**
     * @test
     */
    public function load_registers_expected_services(): void
    {
        $container = $this->loadExtension();

        foreach ($this->expectedServiceIds() as $id) {
            $this->assertTrue($container->hasDefinition($id), \sprintf('Service "%s" not found.', $id));
        }
    }

    /**
     * @test
     */
    public function load_browser_registry_is_private(): void
    {
        $definition = $this->loadExtension()->getDefinition(BrowserRegistry::class);

        $this->assertFalse($definition->isPublic());
    }

    /**
     * @test
     */
    public function load_aliases_kernel_booter_to_standalone_by_default(): void
    {
        $container = $this->loadExtension();

        $this->assertTrue($container->hasAlias(KernelBooter::class));
        $this->assertSame(
            StandaloneKernelBooter::class,
            (string) $container->getAlias(KernelBooter::class),
        );
    }

    /**
     * @test
     */
    public function load_registers_standalone_kernel_booter_with_config(): void
    {
        $container = $this->loadExtension();
        $definition = $container->getDefinition(StandaloneKernelBooter::class);

        $this->assertSame(StandaloneKernelBooter::class, $definition->getClass());
        $this->assertSame([null, 'test', true], $definition->getArguments());
    }

    /**
     * @test
     */
    public function load_standalone_kernel_booter_is_private(): void
    {
        $definition = $this->loadExtension()->getDefinition(StandaloneKernelBooter::class);

        $this->assertFalse($definition->isPublic());
    }

    /**
     * @test
     */
    public function load_configures_browser_options_with_defaults(): void
    {
        $container = $this->loadExtension();
        $definition = $container->getDefinition(BrowserOptions::class);

        $this->assertSame(BrowserOptions::class, $definition->getClass());
        $this->assertSame(
            [null, null, './var/browser/source', false, true, true, './var/browser/screenshots', './var/browser/console-logs', false, null],
            $definition->getArguments(),
        );
    }

    /**
     * @test
     */
    public function load_configures_browser_options_with_custom_values(): void
    {
        $container = $this->loadExtension([
            'kernel_browser_class' => 'App\\CustomKernelBrowser',
            'panther_browser_class' => 'App\\CustomPantherBrowser',
            'source_dir' => '/custom/source',
            'source_debug' => true,
            'follow_redirects' => false,
            'catch_exceptions' => false,
            'screenshot_dir' => '/custom/screenshots',
            'console_log_dir' => '/custom/console-logs',
            'always_start_webserver' => true,
            'panther_browser' => 'firefox',
        ]);

        $definition = $container->getDefinition(BrowserOptions::class);

        $this->assertSame(
            ['App\\CustomKernelBrowser', 'App\\CustomPantherBrowser', '/custom/source', true, false, false, '/custom/screenshots', '/custom/console-logs', true, 'firefox'],
            $definition->getArguments(),
        );
    }

    /**
     * @test
     */
    public function load_browser_options_is_private(): void
    {
        $definition = $this->loadExtension()->getDefinition(BrowserOptions::class);

        $this->assertFalse($definition->isPublic());
    }

    /**
     * @test
     */
    public function load_browser_factory_references_booter_registry_and_options(): void
    {
        $container = $this->loadExtension();
        $definition = $container->getDefinition(BrowserFactory::class);

        $this->assertSame(BrowserFactory::class, $definition->getClass());
        $this->assertCount(3, $definition->getArguments());
        $this->assertReference(KernelBooter::class, $definition->getArgument(0));
        $this->assertReference(BrowserRegistry::class, $definition->getArgument(1));
        $this->assertReference(BrowserOptions::class, $definition->getArgument(2));
    }

    /**
     * @test
     */
    public function load_browser_factory_is_private(): void
    {
        $this->assertFalse(
            $this->loadExtension()->getDefinition(BrowserFactory::class)->isPublic(),
        );
    }

    /**
     * @test
     */
    public function load_artifact_sink_uses_behat_output(): void
    {
        $container = $this->loadExtension();
        $definition = $container->getDefinition(ArtifactSink::class);

        $this->assertSame(BehatOutputArtifactSink::class, $definition->getClass());
    }

    /**
     * @test
     */
    public function load_artifact_sink_is_private(): void
    {
        $this->assertFalse(
            $this->loadExtension()->getDefinition(ArtifactSink::class)->isPublic(),
        );
    }

    /**
     * @test
     */
    public function load_artifact_collector_references_registry_and_sink(): void
    {
        $container = $this->loadExtension();
        $definition = $container->getDefinition(ArtifactCollector::class);

        $this->assertSame(ArtifactCollector::class, $definition->getClass());
        $this->assertCount(2, $definition->getArguments());
        $this->assertReference(BrowserRegistry::class, $definition->getArgument(0));
        $this->assertReference(ArtifactSink::class, $definition->getArgument(1));
    }

    /**
     * @test
     */
    public function load_artifact_collector_is_private(): void
    {
        $this->assertFalse(
            $this->loadExtension()->getDefinition(ArtifactCollector::class)->isPublic(),
        );
    }

    /**
     * @test
     */
    public function load_context_initializer_references_factory_registry_and_booter(): void
    {
        $container = $this->loadExtension();
        $definition = $container->getDefinition(BrowserContextInitializer::class);

        $this->assertSame(BrowserContextInitializer::class, $definition->getClass());
        $this->assertCount(3, $definition->getArguments());
        $this->assertReference(BrowserFactory::class, $definition->getArgument(0));
        $this->assertReference(BrowserRegistry::class, $definition->getArgument(1));
        $this->assertReference(KernelBooter::class, $definition->getArgument(2));
    }

    /**
     * @test
     */
    public function load_tags_context_initializer(): void
    {
        $container = $this->loadExtension();
        $definition = $container->getDefinition(BrowserContextInitializer::class);

        $this->assertSame([ContextExtension::INITIALIZER_TAG => [[]]], $definition->getTags());
    }

    /**
     * @test
     */
    public function load_artifact_listener_references_collector_and_booter(): void
    {
        $container = $this->loadExtension();
        $definition = $container->getDefinition(ArtifactListener::class);

        $this->assertSame(ArtifactListener::class, $definition->getClass());
        $this->assertCount(2, $definition->getArguments());
        $this->assertReference(ArtifactCollector::class, $definition->getArgument(0));
        $this->assertReference(KernelBooter::class, $definition->getArgument(1));
    }

    /**
     * @test
     */
    public function load_tags_artifact_listener(): void
    {
        $container = $this->loadExtension();
        $definition = $container->getDefinition(ArtifactListener::class);

        $this->assertSame([EventDispatcherExtension::SUBSCRIBER_TAG => [[]]], $definition->getTags());
    }

    /**
     * @test
     * @runInSeparateProcess
     */
    public function load_aliases_kernel_booter_to_symfony_extension_when_available(): void
    {
        \class_alias(FooBar::class, self::FOB_SYMFONY_EXTENSION);

        $container = $this->loadExtension();

        $this->assertTrue($container->hasAlias(KernelBooter::class));
        $this->assertSame(
            SymfonyExtensionKernelBooter::class,
            (string) $container->getAlias(KernelBooter::class),
        );
    }

    /**
     * @test
     * @runInSeparateProcess
     */
    public function load_registers_symfony_extension_kernel_booter_when_available(): void
    {
        \class_alias(FooBar::class, self::FOB_SYMFONY_EXTENSION);

        $container = $this->loadExtension();

        $this->assertTrue($container->hasDefinition(SymfonyExtensionKernelBooter::class));

        $definition = $container->getDefinition(SymfonyExtensionKernelBooter::class);
        $this->assertSame(SymfonyExtensionKernelBooter::class, $definition->getClass());

        $this->assertCount(1, $definition->getArguments());
        $this->assertReference('fob_symfony.kernel', $definition->getArgument(0));
    }

    /**
     * @return string[]
     */
    private function expectedServiceIds(): array
    {
        return [
            BrowserRegistry::class,
            BrowserOptions::class,
            BrowserFactory::class,
            ArtifactSink::class,
            ArtifactCollector::class,
            BrowserContextInitializer::class,
            ArtifactListener::class,
        ];
    }

    private function assertReference(string $expected, mixed $actual): void
    {
        $this->assertInstanceOf(Reference::class, $actual);
        $this->assertSame($expected, (string) $actual);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function loadExtension(array $overrides = []): ContainerBuilder
    {
        $container = new ContainerBuilder();

        (new BrowserExtension())->load($container, \array_merge([
            'kernel_class' => null,
            'env' => 'test',
            'debug' => true,
            'kernel_browser_class' => null,
            'panther_browser_class' => null,
            'source_dir' => './var/browser/source',
            'source_debug' => false,
            'follow_redirects' => true,
            'catch_exceptions' => true,
            'screenshot_dir' => './var/browser/screenshots',
            'console_log_dir' => './var/browser/console-logs',
            'always_start_webserver' => false,
            'panther_browser' => null,
        ], $overrides));

        return $container;
    }
}

class FooBar
{
}

<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Behat\Driver;

use Behat\MinkExtension\ServiceContainer\Driver\DriverFactory;
use FriendsOfBehat\SymfonyExtension\ServiceContainer\SymfonyExtension;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
final class PlaywrightDriverFactory implements DriverFactory
{
    public function getDriverName(): string
    {
        return 'zenstruck_playwright';
    }

    public function supportsJavascript(): bool
    {
        return true;
    }

    public function configure(ArrayNodeDefinition $builder): void
    {
        $options = $builder->children();
        $options->scalarNode('browser_class');
        $options->scalarNode('source_dir');
        $options->booleanNode('source_debug');
        $options->booleanNode('follow_redirects');
        $options->booleanNode('catch_exceptions');
        $options->scalarNode('screenshot_dir');
        $options->scalarNode('console_log_dir');
        $options->integerNode('default_timeout')->min(1);
    }

    public function buildDriver(array $config): Definition
    {
        return new Definition(PlaywrightDriver::class, [
            new Reference(SymfonyExtension::DRIVER_KERNEL_ID),
            '%mink.base_url%',
            $config,
        ]);
    }
}

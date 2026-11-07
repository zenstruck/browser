<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Behat\Config\Config;
use Behat\Config\Extension;
use Behat\Config\Filter\TagFilter;
use Behat\Config\Profile;
use Behat\Config\Suite;
use Behat\MinkExtension\Context\MinkContext;
use Behat\MinkExtension\ServiceContainer\MinkExtension;
use FriendsOfBehat\SymfonyExtension\ServiceContainer\SymfonyExtension;
use Zenstruck\Browser\Behat\BrowserExtension;
use Zenstruck\Browser\Behat\BrowserSteps;
use Zenstruck\Browser\Tests\Behat\FeatureContext;
use Zenstruck\Browser\Tests\Behat\MirrorContext;
use Zenstruck\Browser\Tests\Behat\TurboContext;
use Zenstruck\Browser\Tests\Fixture\Kernel;

$mirrorSuite = static function(string $name, string $session, string ...$paths): Suite {
    return (new Suite($name, ['mink_session' => $session]))
        ->withPaths('tests/Behat/mirror/shared.feature', ...$paths)
        ->withContexts(MinkContext::class, BrowserSteps::class, MirrorContext::class)
        ->withFilter(new TagFilter('~@skip'));
};

$playwrightPaths = ['tests/Behat/mirror/playwright', 'tests/Behat/mirror/navigation.feature'];

return (new Config())->withProfile(
    (new Profile('default', ['gherkin' => [
        'cache' => 'var/cache/behat-gherkin',
        'filters' => ['tags' => '~@pending'],
    ]]))
        ->withExtension(new Extension(SymfonyExtension::class, [
            'bootstrap' => 'vendor/autoload.php',
            'kernel' => ['class' => Kernel::class, 'environment' => 'behat'],
        ]))
        ->withExtension(new Extension(MinkExtension::class, [
            'base_url' => 'http://localhost',
            'files_path' => __DIR__.'/../Fixture/files',
            'sessions' => [
                'kernel' => ['zenstruck_kernel' => null],
                'playwright' => ['zenstruck_playwright' => null],
            ],
        ]))
        ->withExtension(new Extension(BrowserExtension::class))
        ->withSuite((new Suite('browser'))
            ->withPaths('tests/Behat/features')
            ->withContexts(MinkContext::class, BrowserSteps::class, FeatureContext::class))
        ->withSuite($mirrorSuite('kernel', 'kernel', 'tests/Behat/mirror/kernel.feature'))
        ->withSuite($mirrorSuite('playwright', 'playwright', ...$playwrightPaths))
        ->withSuite($mirrorSuite('turbo', 'playwright', 'tests/Behat/mirror/turbo.feature', ...$playwrightPaths)
            ->withContexts(TurboContext::class))
)->withProfile(
    (new Profile('failure', ['suites' => ['browser' => false, 'kernel' => false, 'playwright' => false, 'turbo' => false]]))
        ->withSuite((new Suite('failure'))
            ->withPaths('tests/Behat/fixtures')
            ->withContexts(MinkContext::class))
);

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
use Behat\Config\Profile;
use Behat\Config\Suite;
use Behat\MinkExtension\Context\MinkContext;
use Behat\MinkExtension\ServiceContainer\MinkExtension;
use FriendsOfBehat\SymfonyExtension\ServiceContainer\SymfonyExtension;
use Zenstruck\Browser\Behat\BrowserExtension;
use Zenstruck\Browser\Behat\BrowserSteps;
use Zenstruck\Browser\Tests\Behat\FeatureContext;
use Zenstruck\Browser\Tests\Fixture\Kernel;

return (new Config())->withProfile(
    (new Profile('default', ['gherkin' => ['cache' => 'var/cache/behat-gherkin']]))
        ->withExtension(new Extension(SymfonyExtension::class, [
            'bootstrap' => 'vendor/autoload.php',
            'kernel' => ['class' => Kernel::class, 'environment' => 'behat'],
        ]))
        ->withExtension(new Extension(MinkExtension::class, [
            'base_url' => 'http://localhost',
            'sessions' => [
                'kernel' => ['zenstruck_kernel' => null],
                'playwright' => ['zenstruck_playwright' => null],
            ],
        ]))
        ->withExtension(new Extension(BrowserExtension::class))
        ->withSuite((new Suite('browser'))
            ->withPaths('tests/Behat/features')
            ->withContexts(MinkContext::class, BrowserSteps::class, FeatureContext::class))
)->withProfile(
    (new Profile('failure', ['suites' => ['browser' => false]]))
        ->withSuite((new Suite('failure'))
            ->withPaths('tests/Behat/fixtures')
            ->withContexts(MinkContext::class))
);

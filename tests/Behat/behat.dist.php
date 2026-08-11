<?php

declare(strict_types=1);

use Behat\Config\Config;
use Behat\Config\Extension;
use Behat\Config\Profile;
use Behat\Config\Suite;
use Zenstruck\Browser\Bridge\Behat\BrowserExtension;
use Zenstruck\Browser\Tests\Behat\Context\BrowserContext;
use Zenstruck\Browser\Tests\Fixture\Kernel;

$root = __DIR__;
$varRoot = $root.'/../../var/browser';

$default = (new Profile('default'))
    ->withSuite(
        (new Suite('default'))
            ->withPaths($root.'/features')
            ->withContexts(BrowserContext::class),
    )
    ->withExtension(new Extension(BrowserExtension::class, [
        'kernel_class' => Kernel::class,
        'env' => 'test',
        'debug' => true,
        'source_dir' => $varRoot.'/source',
        'screenshot_dir' => $varRoot.'/screenshots',
        'console_log_dir' => $varRoot.'/console-logs',
    ]))
;

return (new Config())->withProfile($default);

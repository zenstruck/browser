<?php

namespace Zenstruck\Browser\Tests;

use Symfony\Component\Panther\PantherTestCase;
use Zenstruck\Browser\HttpBrowser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class HttpBrowserTest extends PantherTestCase
{
    use BrowserKitBrowserTests;

    protected function browser(): HttpBrowser
    {
        return $this->httpBrowser();
    }
}

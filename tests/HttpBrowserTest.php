<?php

namespace Zenstruck\Browser\Tests;

use Symfony\Component\Panther\PantherTestCase;
use Zenstruck\Browser\HttpBrowser;
use Zenstruck\Browser\Test\HasHttpBrowser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class HttpBrowserTest extends PantherTestCase
{
    use BrowserKitBrowserTests, HasHttpBrowser;

    /**
     * @test
     */
    public function can_use_http_browser_as_typehint(): void
    {
        $this->browser()
            ->use(function(HttpBrowser $browser) {
                $browser->visit('/redirect1');
            })
            ->assertOn('/page1')
        ;
    }

    protected function browser(): HttpBrowser
    {
        return $this->httpBrowser();
    }
}

<?php

namespace Zenstruck\Browser\Tests;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Panther\PantherTestCase;
use Zenstruck\Browser\Extension\Email;
use Zenstruck\Browser\HttpBrowser;
use Zenstruck\Browser\Test\HasHttpBrowser;
use Zenstruck\Browser\Tests\Extension\EmailTests;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class HttpBrowserTest extends PantherTestCase
{
    use HasHttpBrowser, BrowserTests, ProfileAwareTests, EmailTests;

    /**
     * @test
     */
    public function the_container_is_injected(): void
    {
        $this->assertInstanceOf(ContainerInterface::class, $this->browser()->container());
    }

    protected function createEmailBrowser(): HttpBrowser
    {
        $browser = new class(static::createHttpBrowserClient()) extends HttpBrowser {
            use Email;
        };

        $browser->setContainer(static::$container);

        return $browser;
    }
}

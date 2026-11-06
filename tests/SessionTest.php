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

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Zenstruck\Browser\Session;
use Zenstruck\Browser\Session\Driver;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SessionTest extends TestCase
{
    /**
     * @test
     *
     * @dataProvider exceptionPages
     */
    #[Test]
    #[DataProvider('exceptionPages')]
    public function exception_page_diagnostics_identify_the_exception(string $content, string $class, string $message): void
    {
        $client = $this->createStub(AbstractBrowser::class);
        $client->method('getCrawler')->willReturn(new Crawler($content));
        $driver = $this->createStub(Driver::class);
        $driver->method('client')->willReturn($client);
        $driver->method('isStarted')->willReturn(true);
        $driver->method('getStatusCode')->willReturn(500);
        $driver->method('rawContent')->willReturn($content);
        $session = new Session($driver);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessageMatches('/^'.\preg_quote('The last request threw an exception: '.$class.' - '.$message, '/').'$/D');

        $session->page();
    }

    public static function exceptionPages(): iterable
    {
        yield 'legacy HTML' => [
            '<div class="trace-details"><span class="trace-class">App\CustomException</span></div><div class="exception-message-wrapper"><p class="exception-message">exception thrown</p></div>',
            'App\CustomException',
            'exception thrown',
        ];

        yield 'CLI dump' => ["App\\CustomException {#123\n  #message: \"exception thrown\"\n}", 'App\CustomException', 'exception thrown'];

        $stack = '<div class="trace-details"><span class="trace-class">Kernel</span></div>';

        yield 'single namespaced exception' => [
            '<div class="exceptions-header"><div class="exc-single"><p class="exc-subtitle"><abbr title="App\CustomException">CustomException</abbr></p><div class="exc-message">exception thrown</div></div></div>'.$stack,
            'App\CustomException',
            'exception thrown',
        ];

        yield 'single global exception' => [
            '<div class="exceptions-header"><div class="exc-single"><p class="exc-subtitle">RuntimeException</p><div class="exc-message">exception thrown</div></div></div>'.$stack,
            'RuntimeException',
            'exception thrown',
        ];

        yield 'single exception without a message' => [
            '<div class="exceptions-header"><div class="exc-single"><div class="exc-message">App\CustomException</div></div></div>'.$stack,
            'App\CustomException',
            '',
        ];

        yield 'exception chain' => [
            '<div class="exceptions-header"><ol class="exception-chain"><li class="exc-item"><span class="exc-item-class" title="App\CustomException">CustomException</span><span class="exc-item-message">outer exception</span><div class="exc-message">outer exception</div></li><li class="exc-item"><span class="exc-item-class" title="RuntimeException">RuntimeException</span><span class="exc-item-message">inner exception</span></li></ol></div>'.$stack,
            'App\CustomException',
            'outer exception',
        ];

        yield 'exception chain without an outer message' => [
            '<div class="exceptions-header"><ol class="exception-chain"><li class="exc-item"><span class="exc-item-class" title="App\CustomException">CustomException</span><div class="exc-message">App\CustomException</div></li><li class="exc-item"><span class="exc-item-class" title="RuntimeException">RuntimeException</span><span class="exc-item-message">inner exception</span></li></ol></div>'.$stack,
            'App\CustomException',
            '',
        ];
    }
}

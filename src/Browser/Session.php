<?php

namespace Zenstruck\Browser;

use Behat\Mink\Element\DocumentElement;
use Behat\Mink\Exception\DriverException;
use Behat\Mink\Session as MinkSession;
use Behat\Mink\WebAssert;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Zenstruck\Assert as ZenstruckAssert;
use Zenstruck\Browser\Session\Assert;
use Zenstruck\Browser\Session\Driver;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 *
 * @method Driver getDriver()
 */
final class Session extends MinkSession
{
    public function __construct(Driver $driver)
    {
        parent::__construct($driver);
    }

    /**
     * @param mixed $what
     */
    public static function varDump($what): void
    {
        \function_exists('dump') ? dump($what) : \var_dump($what);
    }

    public function client(): AbstractBrowser
    {
        return $this->getDriver()->client();
    }

    public function assert(): Assert
    {
        $this->ensureNoException();

        return new Assert(new WebAssert($this));
    }

    public function page(): DocumentElement
    {
        $this->ensureNoException();

        return $this->getPage();
    }

    public function isRedirect(): bool
    {
        return $this->getStatusCode() >= 300 && $this->getStatusCode() < 400;
    }

    public function json(): Json
    {
        if (str_contains((string) $this->getResponseHeader('content-type'), 'json')) {
            return new Json($this->page()->getContent());
        }

        throw new DriverException('This is not a JSON response.');
    }

    public function request(string $method, string $url, ?HttpOptions $options = null): void
    {
        if (!$this->isStarted()) {
            $this->start();
        }

        $this->getDriver()->request(\mb_strtoupper($method), $url, $options ?? new HttpOptions());
    }

    /**
     * @param class-string|callable $expectedException
     */
    public function expectException($expectedException, ?string $expectedMessage = null): void
    {
        $this->getDriver()->expectException($expectedException, $expectedMessage);
    }

    public function source(): string
    {
        try {
            $ret = "URL: {$this->getCurrentUrl()} ({$this->getStatusCode()})\n\n";

            foreach ($this->getResponseHeaders() as $header => $values) {
                foreach ((array) $values as $value) {
                    $ret .= "{$header}: {$value}\n";
                }
            }
        } catch (DriverException $e) {
            $ret = "URL: {$this->getCurrentUrl()}\n";
        }

        $ret .= "\n";

        try {
            $ret .= $this->json();
        } catch (DriverException $e) {
            $ret .= \trim($this->getDriver()->getContent());
        }

        return $ret;
    }

    public function dump(?string $selector = null): void
    {
        if (!$selector) {
            self::varDump($this->source());

            return;
        }

        try {
            self::varDump($this->json()->search($selector));

            return;
        } catch (DriverException $e) {
        }

        $elements = (new Crawler($this->page()->getContent()))->filter($selector);

        if (0 === $elements->count()) {
            throw new \RuntimeException("Element \"{$selector}\" not found.");
        }

        $elements->each(function(Crawler $node) {
            self::varDump($node->outerHtml());
        });
    }

    public function exit(): void
    {
        $this->getDriver()->quit();
        exit(1);
    }

    private function ensureNoException(): void
    {
        if (!$this->isStarted()) {
            ZenstruckAssert::fail('A request has not yet been made.');
        }

        $crawler = $this->client()->getCrawler();

        if (!\count($exceptionClassNode = $crawler->filter('.trace-details .trace-class')->first())) {
            return;
        }

        $messageNode = $crawler->filter('.exception-message-wrapper .exception-message')->first();

        ZenstruckAssert::fail('The last request threw an exception: %s - %s', [
            \preg_replace('/\s+/', '', $exceptionClassNode->text()),
            \count($messageNode) ? $messageNode->text() : 'unknown message',
        ]);
    }
}

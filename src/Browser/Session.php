<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser;

use Behat\Mink\Element\DocumentElement;
use Behat\Mink\Exception\DriverException;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Behat\Mink\Session as MinkSession;
use Behat\Mink\WebAssert;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
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

    /**
     * @return AbstractBrowser<Request, Response>
     */
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
        if (\str_contains((string) $this->getResponseHeader('content-type'), 'json')) {
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

    public function source(bool $sourceDebug = false): string
    {
        $ret = '';

        // We never want to prepend non-text files with metadata.
        if ($sourceDebug && $this->isTextContent()) {
            $ret .= "<!--\n";

            try {
                $ret .= "URL: {$this->getCurrentUrl()} ({$this->getStatusCode()})\n\n";

                foreach ($this->getResponseHeaders() as $header => $values) {
                    foreach ((array) $values as $value) {
                        $ret .= "{$header}: {$value}\n";
                    }
                }
            } catch (DriverException $e) {
                $ret = "URL: {$this->getCurrentUrl()}\n";
            }

            $ret .= "-->\n";
        }

        try {
            $ret .= $this->json();
        } catch (DriverException $e) {
            $ret .= $this->getDriver()->getContent();
        }

        return $ret;
    }

    public function dump(?string $selector = null): void
    {
        if (!$selector) {
            self::varDump($this->source(true));

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

        $elements->each(static function(Crawler $node) {
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

        // an exception page always carries an error status: this runs before every action and
        // assertion, so successful responses are never inspected
        if (!$this->couldBeExceptionPage()) {
            return;
        }

        $crawler = $this->client()->getCrawler();

        // Symfony < 7.4 renders an html exception page
        if (\count($exceptionClassNode = $crawler->filter('.trace-details .trace-class')->first())) {
            $messageNode = $crawler->filter('.exception-message-wrapper .exception-message')->first();

            $this->failWithException(
                (string) \preg_replace('/\s+/', '', $exceptionClassNode->text()),
                \count($messageNode) ? $messageNode->text() : 'unknown message',
            );
        }

        // 7.4+ dumps the exception with var-dumper, which is not html when rendered from the cli
        // not page(), which calls back into here
        $content = \ltrim($this->getDriver()->getContent());

        if (!\preg_match('/^([A-Za-z_\\\\][\w\\\\]*) \{#\d+/', $content, $exception)) {
            return;
        }

        $this->failWithException(
            $exception[1],
            \preg_match('/#message: "([^"]*)"/', $content, $message) ? $message[1] : 'unknown message',
        );
    }

    private function couldBeExceptionPage(): bool
    {
        try {
            return $this->getStatusCode() >= 400;
        } catch (DriverException) {
            // the driver cannot tell us, so fall through to inspecting the response itself
            return true;
        }
    }

    private function failWithException(string $class, string $message): void
    {
        ZenstruckAssert::fail('The last request threw an exception: %s - %s', [$class, $message]);
    }

    private function isTextContent(): bool
    {
        try {
            return match (true) {
                \str_contains((string) $this->getResponseHeader('Content-Type'), 'text/plain'),
                \str_contains((string) $this->getResponseHeader('Content-Type'), 'xml'), // to cover all possible XML content-types: "application/xml (is recommended as of RFC 7303 (section 4.1)), text/xml "
                \str_contains((string) $this->getResponseHeader('Content-Type'), 'html'), // to cover all possible (x)HTML content-types: "text/html, application/xhtml+xml"
                \str_contains((string) $this->getResponseHeader('Content-Type'), 'json'), // to cover all possible JSON content-types: "application/json, application/ld+json"
                => true,
                default => false,
            };
        } catch (UnsupportedDriverActionException) {
            // We never want to add metadata to source content if Panther is used.
            return false;
        }
    }
}

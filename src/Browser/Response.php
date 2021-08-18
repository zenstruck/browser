<?php

namespace Zenstruck\Browser;

use Behat\Mink\Session;
use PHPUnit\Framework\Assert as PHPUnit;
use Symfony\Component\VarDumper\VarDumper;
use Zenstruck\Browser\Response\DomResponse;
use Zenstruck\Browser\Response\HtmlResponse;
use Zenstruck\Browser\Response\JsonResponse;
use Zenstruck\Browser\Response\XmlResponse;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class Response
{
    private Session $session;

    /**
     * @internal
     */
    final public function __construct(Session $session)
    {
        $this->session = $session;
    }

    final public function statusCode(): int
    {
        return $this->session->getStatusCode();
    }

    /**
     * @internal
     */
    final public static function createFor(Session $session): self
    {
        $contentType = (string) $session->getResponseHeader('content-type');

        if (str_contains($contentType, 'json')) {
            return new JsonResponse($session);
        }

        if (str_contains($contentType, 'html')) {
            return new HtmlResponse($session);
        }

        if (str_contains($contentType, 'xml')) {
            return new XmlResponse($session);
        }

        return new self($session);
    }

    final public function body(): string
    {
        return $this->session->getPage()->getContent();
    }

    final public function assertJson(): JsonResponse
    {
        if (!$this instanceof JsonResponse) {
            PHPUnit::fail('Not a json response.');
        }

        return $this;
    }

    final public function assertXml(): XmlResponse
    {
        if (!$this instanceof XmlResponse) {
            PHPUnit::fail('Not an xml response.');
        }

        return $this;
    }

    final public function assertHtml(): HtmlResponse
    {
        if (!$this instanceof HtmlResponse) {
            PHPUnit::fail('Not an html response.');
        }

        return $this;
    }

    final public function assertDom(): DomResponse
    {
        if (!$this instanceof DomResponse) {
            PHPUnit::fail('Not an DOM response.');
        }

        return $this;
    }

    final public function raw(): string
    {
        return "{$this->rawMetadata()}\n{$this->rawBody()}";
    }

    final public function isSuccessful(): bool
    {
        return $this->statusCode() >= 200 && $this->statusCode() < 300;
    }

    final public function isRedirect(): bool
    {
        return $this->statusCode() >= 300 && $this->statusCode() < 400;
    }

    public function dump(?string $selector = null): void
    {
        if (null !== $selector) {
            throw new \LogicException('$selector cannot be used with this response type.');
        }

        VarDumper::dump($this->raw());
    }

    /**
     * @internal
     */
    final protected function session(): Session
    {
        return $this->session;
    }

    /**
     * @internal
     */
    protected function rawMetadata(): string
    {
        $ret = "URL: {$this->session->getCurrentUrl()} ({$this->statusCode()})\n\n";

        foreach ($this->session->getResponseHeaders() as $header => $values) {
            foreach ($values as $value) {
                $ret .= "{$header}: {$value}\n";
            }
        }

        return $ret;
    }

    /**
     * @internal
     */
    protected function rawBody(): string
    {
        return $this->body();
    }
}

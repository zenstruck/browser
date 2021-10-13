<?php

namespace Zenstruck\Browser;

use Symfony\Component\BrowserKit\AbstractBrowser as Client;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\VarDumper\VarDumper;
use Zenstruck\Assert;
use Zenstruck\Browser\Response\DomResponse;
use Zenstruck\Browser\Response\HtmlResponse;
use Zenstruck\Browser\Response\JsonResponse;
use Zenstruck\Browser\Response\XmlResponse;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class Response
{
    private Client $client;

    /**
     * @internal
     */
    final public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function statusCode(): int
    {
        return $this->client->getInternalResponse()->getStatusCode();
    }

    public function headers(): HeaderBag
    {
        return new HeaderBag($this->client->getInternalResponse()->getHeaders());
    }

    public function currentUrl(): string
    {
        return $this->client->getInternalRequest()->getUri();
    }

    /**
     * @internal
     */
    final public static function createFor(Client $client): self
    {
        $contentType = $client->getInternalResponse()->getHeader('content-type');

        if (str_contains($contentType, 'json')) {
            return new JsonResponse($client);
        }

        if (str_contains($contentType, 'html')) {
            return new HtmlResponse($client);
        }

        if (str_contains($contentType, 'xml')) {
            return new XmlResponse($client);
        }

        return new self($client);
    }

    final public function body(): string
    {
        return $this->client->getInternalResponse()->getContent();
    }

    final public function ensureJson(): JsonResponse
    {
        if (!$this instanceof JsonResponse) {
            Assert::fail('Not a json response.');
        }

        return $this;
    }

    final public function ensureXml(): XmlResponse
    {
        if (!$this instanceof XmlResponse) {
            Assert::fail('Not an xml response.');
        }

        return $this;
    }

    final public function ensureHtml(): HtmlResponse
    {
        if (!$this instanceof HtmlResponse) {
            Assert::fail('Not an html response.');
        }

        return $this;
    }

    final public function ensureDom(): DomResponse
    {
        if (!$this instanceof DomResponse) {
            Assert::fail('Not an DOM response.');
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
    protected function rawMetadata(): string
    {
        return "URL: {$this->currentUrl()} ({$this->statusCode()})\n\n{$this->headers()}";
    }

    /**
     * @internal
     */
    protected function rawBody(): string
    {
        return $this->body();
    }
}

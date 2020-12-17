<?php

namespace Zenstruck\Browser;

use Behat\Mink\Session;
use Zenstruck\Browser\Response\HtmlResponse;
use Zenstruck\Browser\Response\JsonResponse;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class Response
{
    private Session $session;

    final public function __construct(Session $session)
    {
        $this->session = $session;
    }

    final public static function createFor(Session $session): self
    {
        $contentType = $session->getResponseHeader('content-type');

        if (str_contains($contentType, 'json')) {
            return new JsonResponse($session);
        }

        if (str_contains($contentType, 'html')) {
            return new HtmlResponse($session);
        }

        throw new \RuntimeException("Unable to create browser response for \"{$contentType}\".");
    }

    public function body()
    {
        return $this->session->getPage()->getContent();
    }

    final public function raw(): string
    {
        return "{$this->rawMetadata()}\n{$this->rawBody()}";
    }

    /**
     * @return mixed
     */
    abstract public function find(string $selector);

    final protected function session(): Session
    {
        return $this->session;
    }

    protected function rawMetadata(): string
    {
        $ret = "URL: {$this->session->getCurrentUrl()} ({$this->session->getStatusCode()})\n\n";

        foreach ($this->session->getResponseHeaders() as $header => $values) {
            foreach ($values as $value) {
                $ret .= "{$header}: {$value}\n";
            }
        }

        return $ret;
    }

    protected function rawBody(): string
    {
        return $this->body();
    }
}

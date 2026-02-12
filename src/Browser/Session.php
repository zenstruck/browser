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

use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\BrowserKit\Response;
use Zenstruck\Dom;
use Zenstruck\Dom\Session as DomSession;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
abstract class Session implements DomSession
{
    /**
     * @param AbstractBrowser<Request, Response> $client
     */
    public function __construct(private AbstractBrowser $client)
    {
    }

    /**
     * @return AbstractBrowser<Request, Response>
     */
    final public function client(): AbstractBrowser
    {
        return $this->client;
    }

    abstract public function dom(): Dom;

    abstract public function content(): string;

    abstract public function currentUrl(): string;
}

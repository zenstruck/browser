<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Tests\Behat;

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Playwright\Dialog\DialogInterface;
use Zenstruck\Assert;
use Zenstruck\Browser\Behat\BrowserContext;

final class MirrorContext extends BrowserContext
{
    private ?string $confirmation = null;

    #[Then('this scenario is not yet implemented because :reason')]
    public function pending(string $reason): never
    {
        throw new PendingException($reason);
    }

    #[Given('the page starts with :html and then runs:')]
    public function scheduleUpdate(string $html, PyStringNode $script): void
    {
        $browser = $this->playwrightBrowser();
        $client = $browser->client();
        $page = $client->getPage();
        $page->evaluate('html => document.body.insertAdjacentHTML("beforeend", html)', $html);

        $script = $script->getRaw();
        $page->evaluate("() => { setTimeout(() => { {$script} }, 200); }");
    }

    #[Given('I accept the next confirmation')]
    public function acceptConfirmation(): void
    {
        $browser = $this->playwrightBrowser();
        $client = $browser->client();
        $page = $client->getPage();
        $events = $page->events();
        $events->onDialog(function(DialogInterface $dialog): void {
            $this->confirmation = $dialog->message();
            $dialog->accept();
        });
    }

    #[Then('the confirmation should say :message')]
    public function assertConfirmation(string $message): void
    {
        Assert::true($this->confirmation === $message, 'Expected confirmation "{message}", got "{actual}".', [
            'message' => $message,
            'actual' => $this->confirmation,
        ]);
    }
}

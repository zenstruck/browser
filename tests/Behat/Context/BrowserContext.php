<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Tests\Behat\Context;

use Behat\Behat\Context\Context;
use Behat\Step\Then;
use Behat\Step\When;
use Zenstruck\Browser\Bridge\Behat\Context\BrowserAware;
use Zenstruck\Browser\Bridge\Behat\Context\BrowserAwareTrait;
use Zenstruck\Browser\KernelBrowser;

/**
 * Fixture Behat context exercising the {@see BrowserAwareTrait} end-to-end
 * against the existing {@see \Zenstruck\Browser\Tests\Fixture\Kernel}.
 *
 * @author Hugo Hamon <hello@kodero.fr>
 */
final class BrowserContext implements Context, BrowserAware
{
    use BrowserAwareTrait;

    private ?KernelBrowser $currentBrowser = null;

    #[When('I visit :url')]
    public function iVisit(string $url): void
    {
        $this->currentBrowser = $this->browser()->visit($url);
    }

    #[Then('I should see :text')]
    public function iShouldSee(string $text): void
    {
        $this->browserOrFail()->assertSee($text);
    }

    #[Then('the response body should contain :text')]
    public function theResponseBodyShouldContain(string $text): void
    {
        $this->browserOrFail()->assertContains($text);
    }

    #[Then('the response should be successful')]
    public function theResponseShouldBeSuccessful(): void
    {
        $this->browserOrFail()->assertSuccessful();
    }

    #[Then('the response status code should be :code')]
    public function theResponseStatusCodeShouldBe(int $code): void
    {
        $this->browserOrFail()->assertStatus($code);
    }

    private function browserOrFail(): KernelBrowser
    {
        if (!$this->currentBrowser instanceof KernelBrowser) {
            throw new \RuntimeException('No browser has been started in this scenario yet.');
        }

        return $this->currentBrowser;
    }
}

<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Behat;

use Behat\Step\Then;
use Behat\Step\When;
use Zenstruck\Assert;
use Zenstruck\Browser\Behat\Driver\PlaywrightDriver;

final class BrowserSteps extends BrowserContext
{
    #[When('I click the :selector element')]
    public function click(string $selector): void
    {
        $assert = $this->assertSession();
        $element = $assert->elementExists('css', $selector);
        $element->click();
    }

    #[Then("the :selector element's :attribute attribute should contain :value")]
    public function assertAttributeContains(string $selector, string $attribute, string $value): void
    {
        $assert = $this->assertSession();
        $assert->elementAttributeContains('css', $selector, $attribute, $value);
    }

    #[Then("the :selector element's :attribute attribute should not contain :value")]
    public function assertAttributeNotContains(string $selector, string $attribute, string $value): void
    {
        $assert = $this->assertSession();
        $assert->elementAttributeNotContains('css', $selector, $attribute, $value);
    }

    #[When('I clear the :field selection')]
    public function clearSelection(string $field): void
    {
        $assert = $this->assertSession();
        $element = $assert->fieldExists($field);
        $tag = $element->getTagName();

        if ('select' !== $tag || !$element->hasAttribute('multiple')) {
            throw new \LogicException(\sprintf('The "%s" field must be a multi-select to clear its selection.', $field));
        }

        $element->setValue([]);
    }

    #[Then(':value should be selected in :field')]
    public function assertSelected(string $value, string $field): void
    {
        $this->assertSelection($value, $field, true);
    }

    #[Then(':value should not be selected in :field')]
    public function assertNotSelected(string $value, string $field): void
    {
        $this->assertSelection($value, $field, false);
    }

    #[Then('I should not be on :path')]
    public function assertNotOn(string $path): void
    {
        $assert = $this->assertSession();
        $url = $this->locatePath($path);
        $assert->addressNotEquals($url);
    }

    #[Then('the response should be successful')]
    public function assertSuccessful(): void
    {
        $session = $this->getSession();
        $status = $session->getStatusCode();

        Assert::true($status >= 200 && $status < 300, 'Expected a successful response, got HTTP {status}.', ['status' => $status]);
    }

    #[Then('the :header response header should equal :value')]
    public function assertHeaderEquals(string $header, string $value): void
    {
        $assert = $this->assertSession();
        $assert->responseHeaderEquals($header, $value);
    }

    #[Then('the :header response header should contain :value')]
    public function assertHeaderContains(string $header, string $value): void
    {
        $assert = $this->assertSession();
        $assert->responseHeaderContains($header, $value);
    }

    #[Then('the :selector element should be visible')]
    public function assertVisible(string $selector): void
    {
        $browser = $this->playwrightBrowser();
        $browser->assertVisible($selector);
    }

    #[Then('the :selector element should not be visible')]
    public function assertNotVisible(string $selector): void
    {
        $browser = $this->playwrightBrowser();
        $browser->assertNotVisible($selector);
    }

    #[When('I double-click the :selector element')]
    public function doubleClick(string $selector): void
    {
        $browser = $this->playwrightBrowser();
        $browser->doubleClick($selector);
    }

    #[When('I right-click the :selector element')]
    public function rightClick(string $selector): void
    {
        $browser = $this->playwrightBrowser();
        $browser->rightClick($selector);
    }

    private function assertSelection(string $value, string $field, bool $selected): void
    {
        $assert = $this->assertSession();
        $element = $assert->fieldExists($field);
        $session = $this->getSession();
        $driver = $session->getDriver();

        if ($driver instanceof PlaywrightDriver) {
            $xpath = $element->getXpath();
            $driver->waitForAssertion('fieldValueContains', [$xpath, $value, !$selected]);
        }

        $actual = \in_array($value, (array) $element->getValue(), true);
        $message = $selected ? 'Expected "{value}" to be selected in "{field}".' : 'Expected "{value}" to not be selected in "{field}".';

        Assert::true($actual === $selected, $message, ['value' => $value, 'field' => $field]);
    }
}

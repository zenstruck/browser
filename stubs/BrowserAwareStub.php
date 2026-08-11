<?php

use Behat\Behat\Context\Context;
use Zenstruck\Browser\Bridge\Behat\Context\BrowserAware;
use Zenstruck\Browser\Bridge\Behat\Context\BrowserAwareTrait;

class BrowserAwareStub implements BrowserAware, Context
{
    use BrowserAwareTrait;
}

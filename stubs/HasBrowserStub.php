<?php

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Browser\Test\HasBrowser;

class HasBrowserStub extends KernelTestCase
{
    use HasBrowser;
}

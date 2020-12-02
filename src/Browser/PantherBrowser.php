<?php

namespace Zenstruck\Browser;

use Symfony\Component\Panther\Client;
use Zenstruck\Browser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @method Client inner()
 */
class PantherBrowser extends Browser
{
    final public function __construct(Client $inner)
    {
        parent::__construct($inner);
    }
}

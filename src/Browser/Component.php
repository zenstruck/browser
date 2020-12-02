<?php

namespace Zenstruck\Browser;

use Zenstruck\Browser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class Component
{
    private Browser $browser;

    final public function __construct(Browser $browser)
    {
        $this->browser = $browser;

        $this->preActions();
        $this->preAssertions();
    }

    final public function browser(): Browser
    {
        return $this->browser;
    }

    /**
     * Runs when component is created. Override to add your own
     * component pre-actions (ie navigate to page).
     */
    protected function preActions(): void
    {
    }

    /**
     * Runs when component is created. Override to add your own
     * component pre-assertions (runs after preActions()).
     */
    protected function preAssertions(): void
    {
    }
}

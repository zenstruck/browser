<?php

namespace Zenstruck\Browser\Test;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Zenstruck\Browser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait HasBrowser
{
    final protected function browser(): Browser
    {
        $browser = $this->createBrowser()
            ->setSourceDir($_SERVER['BROWSER_SOURCE_DIR'] ?? './var/browser/source')
        ;

        BrowserExtension::registerBrowser($browser);

        if (!$this instanceof KernelTestCase) {
            $this->configureBrowser($browser);

            return $browser;
        }

        if (!static::$booted) {
            static::bootKernel();
        }

        if ($browser instanceof ContainerAwareInterface) {
            $browser->setContainer(static::$container);
        }

        $this->configureBrowser($browser);

        return $browser;
    }

    /**
     * Override to configure the Browser's initial state/options.
     */
    protected function configureBrowser(Browser $browser): void
    {
    }

    abstract protected function createBrowser(): Browser;
}

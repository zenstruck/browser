<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Internal;

use Symfony\Bundle\FrameworkBundle\KernelBrowser as SymfonyKernelBrowser;
use Zenstruck\Browser;
use Zenstruck\Browser\ApiBrowser;
use Zenstruck\Browser\HtmlBrowser;
use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Browser\Session\Driver\BrowserKitDriver;
use Zenstruck\Browser\Test\BrowserExtension;

/**
 * @internal
 *
 * @phpstan-require-extends Browser
 *
 * @method SymfonyKernelBrowser client()
 */
trait KernelBrowserBehavior
{
    /**
     * @internal
     */
    final public function __construct(SymfonyKernelBrowser $client, array $options = [])
    {
        if ($this instanceof KernelBrowser && !$this instanceof HtmlBrowser) {
            trigger_deprecation('zenstruck/browser', '1.16', 'Extending %s directly is deprecated, extend %s instead.', KernelBrowser::class, HtmlBrowser::class);
        }

        parent::__construct(new BrowserKitDriver($client), $options); // @phpstan-ignore argument.type

        if ($this instanceof ApiBrowser) {
            $this->browserState->apiBrowser = $this;
        }

        if ($this instanceof KernelBrowser) {
            $this->browserState->htmlBrowser = $this;
        }

        if (isset($options['_browser_state'])) {
            return;
        }

        $client->followRedirects((bool) ($options['follow_redirects'] ?? true));

        if (!($options['catch_exceptions'] ?? true)) {
            $this->throwExceptions();
        }
    }

    /**
     * @see SymfonyKernelBrowser::disableReboot()
     *
     * @return static
     */
    final public function disableReboot(): self
    {
        $this->client()->disableReboot();

        return $this;
    }

    /**
     * @see SymfonyKernelBrowser::enableReboot()
     *
     * @return static
     */
    final public function enableReboot(): self
    {
        $this->client()->enableReboot();

        return $this;
    }

    public function api(): ApiBrowser
    {
        $browser = $this->browserState->apiBrowser ?? $this->createApiView();
        BrowserExtension::registerBrowser($browser);

        return $browser;
    }

    public function html(): KernelBrowser
    {
        $browser = $this->browserState->htmlBrowser ?? $this->createHtmlView();
        BrowserExtension::registerBrowser($browser);

        return $browser;
    }

    private function createApiView(): ApiBrowser
    {
        $class = $_SERVER['API_BROWSER_CLASS'] ?? ApiBrowser::class;
        self::ensureBrowserClass($class, ApiBrowser::class, 'API_BROWSER_CLASS');
        $browser = new $class($this->client(), ['_browser_state' => $this->browserState]);

        if ($this->defaultHttpOptions) {
            $browser->setDefaultHttpOptions(clone $this->defaultHttpOptions);
        }

        return $browser;
    }

    private function createHtmlView(): KernelBrowser
    {
        $class = $_SERVER['KERNEL_BROWSER_CLASS'] ?? HtmlBrowser::class;
        self::ensureBrowserClass($class, KernelBrowser::class, 'KERNEL_BROWSER_CLASS');

        return new $class($this->client(), ['_browser_state' => $this->browserState]);
    }

    /**
     * @param class-string $parent
     */
    private static function ensureBrowserClass(string $class, string $parent, string $variable): void
    {
        if (!\is_a($class, $parent, true) || !(new \ReflectionClass($class))->isInstantiable()) {
            throw new \LogicException(\sprintf('"%s" env variable must reference a concrete class that extends %s.', $variable, $parent));
        }
    }
}

<?php

namespace Zenstruck\Browser\Test;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\BrowserKit\HttpBrowser as HttpBrowserClient;
use Symfony\Component\Panther\PantherTestCase;
use Zenstruck\Browser\HttpBrowser;

trait HasHttpBrowser
{
    /** @var HttpBrowserClient[] */
    private static array $httpBrowserClients = [];

    /**
     * @internal
     * @after
     */
    final public static function _resetHttpBrowserClients(): void
    {
        self::$httpBrowserClients = [];
    }

    protected function httpBrowser(array $kernelOptions = [], array $pantherOptions = []): HttpBrowser
    {
        $class = $_SERVER['HTTP_BROWSER_CLASS'] ?? HttpBrowser::class;

        if (!\is_a($class, HttpBrowser::class, true)) {
            throw new \RuntimeException(\sprintf('"HTTP_BROWSER_CLASS" env variable must reference a class that extends %s.', HttpBrowser::class));
        }

        $baseUri = $_SERVER['HTTP_BROWSER_URI'] ?? null;

        if (!$baseUri && !$this instanceof PantherTestCase) {
            throw new \RuntimeException(\sprintf('If not using "HTTP_BROWSER_URI", your TestCase must extend "%s".', PantherTestCase::class));
        }

        if (!$baseUri) {
            self::startWebServer($pantherOptions);

            $baseUri = self::$baseUri;
        }

        // copied from PantherTestCaseTrait::createHttpBrowserClient()
        $client = new HttpBrowserClient();
        $urlComponents = \parse_url($baseUri);
        $host = $urlComponents['host'];

        if (isset($urlComponents['port'])) {
            $host .= ":{$urlComponents['port']}";
        }

        $client->setServerParameter('HTTP_HOST', $host);

        if ('https' === ($urlComponents['scheme'] ?? 'http')) {
            $client->setServerParameter('HTTPS', 'true');
        }

        $browser = new $class(self::$httpBrowserClients[] = $client);

        if ($this instanceof KernelTestCase) {
            if (!static::$booted) {
                static::bootKernel($kernelOptions);
            }

            if (static::$container->has('profiler')) {
                $browser->setProfiler(static::$container->get('profiler'));
            }
        }

        BrowserExtension::registerBrowser($browser);

        return $browser
            ->setSourceDir($_SERVER['BROWSER_SOURCE_DIR'] ?? './var/browser/source')
        ;
    }
}

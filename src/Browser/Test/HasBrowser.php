<?php

namespace Zenstruck\Browser\Test;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\HttpBrowser as HttpBrowserClient;
use Symfony\Component\Panther\Client as PantherClient;
use Symfony\Component\Panther\PantherTestCase;
use Zenstruck\Browser\HttpBrowser;
use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Browser\PantherBrowser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait HasBrowser
{
    /** @var HttpBrowserClient[] */
    private static array $httpBrowserClients = [];
    private static ?PantherClient $primaryPantherClient = null;

    /**
     * @internal
     * @after
     */
    final public static function _resetBrowserClients(): void
    {
        self::$httpBrowserClients = [];
        self::$primaryPantherClient = null;
    }

    protected function pantherBrowser(array $options = [], array $kernelOptions = [], array $managerOptions = []): PantherBrowser
    {
        $browser = PantherBrowser::create(function() use ($options, $kernelOptions, $managerOptions) {
            if (!$this instanceof PantherTestCase) {
                throw new \RuntimeException(\sprintf('The "%s" method can only be used on TestCases that extend "%s".', __METHOD__, PantherTestCase::class));
            }

            $class = $_SERVER['PANTHER_BROWSER_CLASS'] ?? PantherBrowser::class;

            if (!\is_a($class, PantherBrowser::class, true)) {
                throw new \RuntimeException(\sprintf('"PANTHER_BROWSER_CLASS" env variable must reference a class that extends %s.', PantherBrowser::class));
            }

            if (self::$primaryPantherClient) {
                return new $class(static::createAdditionalPantherClient());
            }

            self::$primaryPantherClient = static::createPantherClient(
                \array_merge(['browser' => $_SERVER['PANTHER_BROWSER'] ?? static::CHROME], $options),
                $kernelOptions,
                $managerOptions
            );

            return new $class(self::$primaryPantherClient);
        });

        BrowserExtension::registerBrowser($browser);

        return $browser
            ->setSourceDir($_SERVER['BROWSER_SOURCE_DIR'] ?? './var/browser/source')
            ->setScreenshotDir($_SERVER['BROWSER_SCREENSHOT_DIR'] ?? './var/browser/screenshots')
            ->setConsoleLogDir($_SERVER['BROWSER_CONSOLE_LOG_DIR'] ?? './var/browser/console-logs')
        ;
    }

    protected function httpBrowser(array $kernelOptions = [], array $pantherOptions = []): HttpBrowser
    {
        $browser = HttpBrowser::create(function() use ($kernelOptions, $pantherOptions) {
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

            /** @var HttpBrowser $browser */
            $browser = new $class(self::$httpBrowserClients[] = $client);

            if (!$this instanceof KernelTestCase) {
                return $browser;
            }

            if (!static::$booted) {
                static::bootKernel($kernelOptions);
            }

            if (static::$container->has('profiler')) {
                $browser->setProfiler(static::$container->get('profiler'));
            }

            return $browser;
        });

        BrowserExtension::registerBrowser($browser);

        return $browser
            ->setSourceDir($_SERVER['BROWSER_SOURCE_DIR'] ?? './var/browser/source')
        ;
    }

    protected function kernelBrowser(array $options = []): KernelBrowser
    {
        if (!$this instanceof KernelTestCase) {
            throw new \RuntimeException(\sprintf('The "%s" method can only be used on TestCases that extend "%s".', __METHOD__, KernelTestCase::class));
        }

        $browser = KernelBrowser::create(function() use ($options) {
            $class = $_SERVER['KERNEL_BROWSER_CLASS'] ?? KernelBrowser::class;

            if (!\is_a($class, KernelBrowser::class, true)) {
                throw new \RuntimeException(\sprintf('"KERNEL_BROWSER_CLASS" env variable must reference a class that extends %s.', KernelBrowser::class));
            }

            if ($this instanceof WebTestCase) {
                static::ensureKernelShutdown();

                return new $class(static::createClient($options));
            }

            // reboot kernel before starting browser
            static::bootKernel($options);

            if (!static::$container->has('test.client')) {
                throw new \RuntimeException('The Symfony test client is not enabled.');
            }

            return new $class(static::$container->get('test.client'));
        });

        BrowserExtension::registerBrowser($browser);

        return $browser
            ->setSourceDir($_SERVER['BROWSER_SOURCE_DIR'] ?? './var/browser/source')
        ;
    }
}

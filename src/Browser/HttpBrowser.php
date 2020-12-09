<?php

namespace Zenstruck\Browser;

use Symfony\Component\BrowserKit\HttpBrowser as SymfonyHttpBrowser;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Zenstruck\Browser\Extension\ContainerAware;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @method SymfonyHttpBrowser inner()
 */
class HttpBrowser extends BrowserKitBrowser implements ContainerAwareInterface
{
    use ContainerAware;

    final public function __construct(SymfonyHttpBrowser $inner)
    {
        parent::__construct($inner);
    }

    /**
     * Profile collection must be enabled globally for this feature.
     */
    final public function profile(): Profile
    {
        if (!$this->container()->has('profiler')) {
            throw new \RuntimeException('Profiling is not enabled.');
        }

        if (!$token = $this->inner()->getInternalResponse()->getHeader('x-debug-token')) {
            throw new \RuntimeException('Profiling is not enabled for this request. You must enable profile collection globally when using the HttpBrowser.');
        }

        if (!$profile = $this->container()->get('profiler')->loadProfile($token)) {
            throw new \RuntimeException('Could not find profile for this request.');
        }

        return $profile;
    }
}

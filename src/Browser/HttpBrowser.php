<?php

namespace Zenstruck\Browser;

use Symfony\Component\BrowserKit\HttpBrowser as SymfonyHttpBrowser;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\HttpKernel\Profiler\Profiler;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class HttpBrowser extends BrowserKitBrowser
{
    private ?Profiler $profiler = null;

    final public function __construct(SymfonyHttpBrowser $inner)
    {
        parent::__construct($inner);
    }

    final public function setProfiler(Profiler $profiler): self
    {
        $this->profiler = $profiler;

        return $this;
    }

    /**
     * Profile collection must be enabled globally for this feature.
     */
    final public function profile(): Profile
    {
        if (!$this->profiler) {
            throw new \RuntimeException('The profiler has not been set. Is profiling enabled?');
        }

        if (!$token = $this->inner()->getInternalResponse()->getHeader('x-debug-token')) {
            throw new \RuntimeException('Profiling is not enabled for this request. You must enable profile collection globally when using the HttpBrowser.');
        }

        if (!$profile = $this->profiler->loadProfile($token)) {
            throw new \RuntimeException('Could not find profile for this request.');
        }

        return $profile;
    }
}

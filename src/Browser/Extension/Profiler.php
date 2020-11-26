<?php

namespace Zenstruck\Browser\Extension;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\HttpKernel\Profiler\Profile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @method AbstractBrowser browser()
 */
trait Profiler
{
    final public function profile(): Profile
    {
        if (!$this->browser() instanceof KernelBrowser) {
            throw new \RuntimeException('KernelBrowser not being used.');
        }

        if (false === $profile = $this->browser()->getProfile()) {
            throw new \RuntimeException('Profiler not enabled.');
        }

        return $profile;
    }

    final public function withProfiling(): self
    {
        if (!$this->browser() instanceof KernelBrowser) {
            throw new \RuntimeException('KernelBrowser not being used.');
        }

        $this->browser()->enableProfiler();

        return $this;
    }
}

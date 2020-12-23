<?php

namespace Zenstruck\Browser;

use Symfony\Bundle\FrameworkBundle\KernelBrowser as SymfonyKernelBrowser;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @method SymfonyKernelBrowser inner()
 */
class KernelBrowser extends BrowserKitBrowser implements ProfileAware
{
    final public function __construct(SymfonyKernelBrowser $inner)
    {
        parent::__construct($inner);
    }

    /**
     * By default, exceptions made during a request are caught and converted
     * to responses by Symfony. This disables this behaviour and actually
     * throws the exception.
     *
     * @return static
     */
    final public function throwExceptions(): self
    {
        $this->inner()->catchExceptions(false);

        return $this;
    }

    /**
     * Re-enables catching exceptions.
     *
     * @return static
     */
    final public function catchExceptions(): self
    {
        $this->inner()->catchExceptions(true);

        return $this;
    }

    /**
     * Enable profiling for the next request. Not required if profiling is
     * globally enabled.
     *
     * @return static
     */
    final public function withProfiling(): self
    {
        $this->inner()->enableProfiler();

        return $this;
    }

    final public function actingAs(UserInterface $user, string $firewall = null): self
    {
        if (null === $firewall) {
            $this->inner()->loginUser($user);
        } else {
            $this->inner()->loginUser($user, $firewall);
        }

        return $this;
    }

    final public function profile(): Profile
    {
        if (!$profile = $this->inner()->getProfile()) {
            throw new \RuntimeException('Profiler not enabled for this request. Try calling ->withProfiling() before the request.');
        }

        return $profile;
    }
}

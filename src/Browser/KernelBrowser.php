<?php

namespace Zenstruck\Browser;

use Symfony\Bundle\FrameworkBundle\KernelBrowser as SymfonyKernelBrowser;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\Security\Core\User\UserInterface;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\Proxy;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @extends BrowserKitBrowser<SymfonyKernelBrowser>
 */
class KernelBrowser extends BrowserKitBrowser
{
    final public function __construct(SymfonyKernelBrowser $inner)
    {
        parent::__construct($inner);
    }

    /**
     * @see SymfonyKernelBrowser::disableReboot()
     *
     * @return static
     */
    final public function disableReboot(): self
    {
        $this->inner()->disableReboot();

        return $this;
    }

    /**
     * @see SymfonyKernelBrowser::enableReboot()
     *
     * @return static
     */
    final public function enableReboot(): self
    {
        $this->inner()->enableReboot();

        return $this;
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

    /**
     * @param object|UserInterface|Proxy|Factory $user
     */
    public function actingAs(object $user, ?string $firewall = null): self
    {
        if ($user instanceof Factory) {
            $user = $user->create();
        }

        if ($user instanceof Proxy) {
            $user = $user->object();
        }

        if (!$user instanceof UserInterface) {
            throw new \LogicException(\sprintf('%s() requires the user be an instance of %s.', __METHOD__, UserInterface::class));
        }

        $this->inner()->loginUser(...\array_filter([$user, $firewall]));

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

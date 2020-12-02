<?php

namespace Zenstruck\Browser;

use Symfony\Component\HttpKernel\Profiler\Profile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface ProfileAware
{
    public function profile(): Profile;
}

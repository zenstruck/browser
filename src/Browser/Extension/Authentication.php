<?php

namespace Zenstruck\Browser\Extension;

/**
 * This is a basic authentication extension more for an example. You will
 * likely need to create your own authentication extension or override
 * these methods.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait Authentication
{
    /**
     * @return static
     */
    public function loginAs(string $username, string $password): self
    {
        return $this
            ->visit('/login')
            ->fillField('email', $username)
            ->fillField('password', $password)
            ->press('Login')
        ;
    }

    /**
     * @return static
     */
    public function logout(): self
    {
        return $this->visit('/logout');
    }

    /**
     * @return static
     */
    public function assertLoggedIn(): self
    {
        $this->assertSee('Logout');

        return $this;
    }

    /**
     * @return static
     */
    public function assertLoggedInAs(string $user): self
    {
        $this->assertSee($user);

        return $this;
    }

    /**
     * @return static
     */
    public function assertNotLoggedIn(): self
    {
        $this->assertSee('Login');

        return $this;
    }
}

<?php

namespace Zenstruck\Browser;

/**
 * This is a basic authentication extension more for an example. You will
 * likely need to create your own authentication extension or override
 * these methods.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @mixin Actions
 * @mixin Assertions
 */
trait Authentication
{
    public function loginAs(string $username, string $password): self
    {
        return $this
            ->visit('/login')
            ->fillField('email', $username)
            ->fillField('password', $password)
            ->press('Login')
        ;
    }

    public function logout(): self
    {
        return $this->visit('/logout');
    }

    public function assertLoggedIn(): self
    {
        $this->assertSee('Logout');

        return $this;
    }

    public function assertLoggedInAs(string $user): self
    {
        $this->assertSee($user);

        return $this;
    }

    public function assertNotLoggedIn(): self
    {
        $this->assertSee('Login');

        return $this;
    }
}

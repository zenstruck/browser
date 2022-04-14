<?php

namespace Zenstruck\Browser\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Factory;
use function Zenstruck\Foundry\factory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class KernelBrowserAuthenticationTest extends KernelTestCase
{
    use HasBrowser;

    /**
     * @test
     */
    public function can_act_as_user(): void
    {
        $this->browser()
            ->throwExceptions()
            ->actingAs(new InMemoryUser('kevin', 'pass'))
            ->visit('/user')
            ->assertSee('user: kevin/pass')
        ;
    }

    /**
     * @test
     */
    public function can_act_as_user_with_foundry_factory(): void
    {
        // todo remove this requirement in foundry
        Factory::boot(new Configuration());

        $user = factory(InMemoryUser::class, ['username' => 'kevin', 'password' => 'pass']);

        $this->browser()
            ->throwExceptions()
            ->actingAs($user)
            ->visit('/user')
            ->assertSee('user: kevin/pass')
        ;
    }

    /**
     * @test
     */
    public function can_act_as_user_with_foundry_proxy(): void
    {
        // todo remove this requirement in foundry
        Factory::boot(new Configuration());

        $user = factory(InMemoryUser::class)->create(['username' => 'kevin', 'password' => 'pass']);

        $this->browser()
            ->throwExceptions()
            ->actingAs($user)
            ->visit('/user')
            ->assertSee('user: kevin/pass')
        ;
    }

    /**
     * @test
     */
    public function can_make_authentication_assertions(): void
    {
        // todo remove this requirement in foundry
        Factory::boot(new Configuration());

        $username = 'kevin';
        $user = new InMemoryUser('kevin', 'pass');
        $factory = factory(InMemoryUser::class, ['username' => 'kevin', 'password' => 'pass']);
        $proxy = factory(InMemoryUser::class)->create(['username' => 'kevin', 'password' => 'pass']);

        $this->browser()
            ->assertNotAuthenticated()
            ->actingAs($user)
            ->assertAuthenticated()
            ->assertAuthenticated($username)
            ->assertAuthenticated($user)
            ->assertAuthenticated($factory)
            ->assertAuthenticated($proxy)
            ->visit('/user')
            ->assertAuthenticated()
            ->assertAuthenticated($username)
        ;
    }

    /**
     * @test
     */
    public function can_check_if_not_authenticated_after_request(): void
    {
        $this->browser()
            ->visit('/page1')
            ->assertNotAuthenticated()
            ->assertSeeIn('a', 'a link')
        ;
    }
}

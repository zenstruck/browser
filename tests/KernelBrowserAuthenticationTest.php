<?php

namespace Zenstruck\Browser\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Browser\Tests\Fixture\Kernel;
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

    /**
     * @test
     */
    public function can_authenticate_with_form_login_and_remember_me(): void
    {
        if (Kernel::VERSION_ID < 60000) {
            $this->markTestIncomplete('For some reason, the login token is always remember me, even before expiring the session'); // todo
        }

        $this->browser()
            ->visit('/user')
            ->assertNotSee('user: kevin/pass')
            ->assertNotAuthenticated()
            ->post('/login', ['body' => ['_username' => 'kevin', '_password' => 'pass', '_remember_me' => true]])
            ->assertOn('/')
            ->assertStatus(404)
            ->visit('/page1') // required because the last request was a 404
            ->assertAuthenticated()
            ->assertAuthenticated('kevin')
            ->visit('/user')
            ->assertSee('user: kevin/pass/'.UsernamePasswordToken::class)
            ->use(function(CookieJar $cookies) {
                $this->assertNotNull($cookies->get('REMEMBERME'));
                $cookies->expire('MOCKSESSID');
            })
            ->withProfiling() // required to trigger a security operation
            ->visit('/page1')
            ->assertAuthenticated()
            ->assertAuthenticated('kevin')
            ->visit('/user')
            ->assertSee('user: kevin/pass/'.RememberMeToken::class)
            ->visit('/logout')
            ->assertOn('/')
            ->assertNotAuthenticated()
        ;
    }
}

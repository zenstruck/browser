<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Zenstruck\Browser\Test\HasBrowser;

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
    public function can_make_authentication_assertions(): void
    {
        $username = 'kevin';
        $user = new InMemoryUser('kevin', 'pass');

        $this->browser()
            ->assertNotAuthenticated()
            ->actingAs($user)
            ->assertAuthenticated()
            ->assertAuthenticated($username)
            ->assertAuthenticated($user)
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
        $this->markTestIncomplete('For some reason, under certain dependency conditions, the login token is always remember me, even before expiring the session'); // todo

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

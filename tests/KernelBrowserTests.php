<?php

namespace Zenstruck\Browser\Tests;

use Symfony\Component\Security\Core\User\InMemoryUser;
use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Factory;
use function Zenstruck\Foundry\factory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait KernelBrowserTests
{
    use BrowserKitBrowserTests;

    /**
     * @test
     */
    public function can_use_kernel_browser_as_typehint(): void
    {
        $this->browser()
            ->use(function(KernelBrowser $browser) {
                $browser->visit('/redirect1');
            })
            ->assertOn('/page1')
        ;
    }

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
    public function can_enable_exception_throwing(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('exception thrown');

        $this->browser()
            ->throwExceptions()
            ->visit('/exception')
        ;
    }

    /**
     * @test
     */
    public function can_re_enable_catching_exceptions(): void
    {
        $browser = $this->browser();

        try {
            $browser->throwExceptions()->visit('/exception');
        } catch (\Exception $e) {
            $browser
                ->catchExceptions()
                ->visit('/exception')
                ->assertStatus(500)
            ;

            return;
        }

        $this->fail('Exception was not caught.');
    }

    /**
     * @test
     */
    public function can_enable_the_profiler(): void
    {
        $profile = $this->browser()
            ->withProfiling()
            ->visit('/page1')
            ->profile()
        ;

        $this->assertTrue($profile->hasCollector('request'));
    }

    protected function browser(): KernelBrowser
    {
        return $this->kernelBrowser();
    }
}

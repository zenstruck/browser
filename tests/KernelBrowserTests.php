<?php

namespace Zenstruck\Browser\Tests;

use Symfony\Bundle\FrameworkBundle\KernelBrowser as SymfonyKernelBrowser;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\User;
use Zenstruck\Browser\KernelBrowser;

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
        if (!\method_exists(SymfonyKernelBrowser::class, 'loginUser')) {
            $this->markTestSkipped(SymfonyKernelBrowser::class.'::loginUser() is only available in Symfony 5.1+.');
        }

        $userClass = \class_exists(InMemoryUser::class) ? InMemoryUser::class : User::class;

        $this->browser()
            ->throwExceptions()
            ->actingAs(new $userClass('kevin', 'pass'))
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

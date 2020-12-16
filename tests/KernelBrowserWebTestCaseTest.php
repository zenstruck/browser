<?php

namespace Zenstruck\Browser\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class KernelBrowserWebTestCaseTest extends WebTestCase
{
    use KernelBrowserTests;

    /**
     * @test
     */
    public function calling_browser_ensures_kernel_is_shutdown(): void
    {
        static::bootKernel();

        $this->browser()
            ->visit('/page1')
            ->assertSuccessful()
        ;
    }

    /**
     * @test
     */
    public function can_use_native_web_test_case_assertions(): void
    {
        $this->browser()
            ->visit('/invalid-page')
            ->assertStatus(404)
        ;

        self::assertResponseStatusCodeSame(404);
    }
}

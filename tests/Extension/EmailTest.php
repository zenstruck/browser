<?php

namespace Zenstruck\Browser\Tests\Extension;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Browser;
use Zenstruck\Browser\Extension\Email\TestEmail;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Browser\Tests\Fixture\EmailBrowser;

/**
 * @method EmailBrowser browser()
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class EmailTest extends KernelTestCase
{
    use HasBrowser;

    /**
     * @test
     */
    public function assert_no_email_sent(): void
    {
        $this->browser()
            ->withProfiling()
            ->visit('/page1')
            ->assertSuccessful()
            ->assertNoEmailSent()
        ;
    }

    /**
     * @test
     */
    public function assert_email_sent(): void
    {
        $this->browser()
            ->throwExceptions()
            ->withProfiling()
            ->visit('/send-email')
            ->assertSuccessful()
            ->assertEmailSentTo('kevin@example.com', 'email subject')
            ->assertEmailSentTo('kevin@example.com', function(TestEmail $email) {
                $email
                    ->assertTo('kevin@example.com', 'Kevin')
                    ->assertFrom('webmaster@example.com')
                    ->assertCc('cc@example.com')
                    ->assertBcc('bcc@example.com')
                    ->assertReplyTo('reply@example.com')
                    ->assertHtmlContains('html body')
                    ->assertTextContains('text body')
                    ->assertContains('body')
                    ->assertHasFile('attachment.txt', 'text/plain', "attachment contents\n")
                ;
            })
        ;
    }

    protected function createBrowser(): Browser
    {
        return new EmailBrowser(static::$container->get('test.client'));
    }
}

<?php

namespace Zenstruck\Browser\Tests\Component;

use Zenstruck\Browser\Component\Mailer;
use Zenstruck\Browser\Component\Mailer\TestEmail;
use Zenstruck\Browser\Tests\Fixture\CustomTestEmail;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait EmailTests
{
    /**
     * @test
     */
    public function assert_no_email_sent(): void
    {
        $this->browser()
            ->visit('/page1')
            ->assertSuccessful()
            ->use(function(Mailer $mailer) {
                $mailer->assertNoEmailSent();
            })
        ;
    }

    /**
     * @test
     */
    public function assert_email_sent(): void
    {
        $this->browser()
            ->visit('/send-email')
            ->assertSuccessful()
            ->use(function(Mailer $mailer) {
                $mailer
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

                        // TestEmail can call underlying Symfony\Component\Mime\Email methods
                        $this->assertSame('Kevin', $email->getTo()[0]->getName());
                    })
                ;
            })
        ;
    }

    /**
     * @test
     */
    public function can_use_custom_test_email_class(): void
    {
        $this->browser()
            ->visit('/send-email')
            ->use(function(Mailer $mailer) {
                $mailer->assertEmailSentTo('kevin@example.com', function(CustomTestEmail $email) {
                    $email->assertHasPostmarkTag('reset-password');
                });
            })
        ;
    }
}

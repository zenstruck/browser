<?php

namespace Zenstruck\Browser\Tests\Extension;

use Zenstruck\Browser;
use Zenstruck\Browser\Component\Email\TestEmail;
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
        $this->createEmailBrowser()
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
        $this->createEmailBrowser()
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

                // TestEmail can call underlying Symfony\Component\Mime\Email methods
                $this->assertSame('Kevin', $email->getTo()[0]->getName());
            })
        ;
    }

    /**
     * @test
     */
    public function can_use_custom_test_email_class(): void
    {
        $this->createEmailBrowser()
            ->visit('/send-email')
            ->assertEmailSentTo('kevin@example.com', function(CustomTestEmail $email) {
                $email->assertHasPostmarkTag('reset-password');
            })
        ;
    }

    abstract protected function createEmailBrowser(): Browser;
}

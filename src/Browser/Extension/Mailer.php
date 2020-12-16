<?php

namespace Zenstruck\Browser\Extension;

use Zenstruck\Browser\Component\Mailer as MailerComponent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait Mailer
{
    /**
     * @see MailerComponent::assertNoEmailSent()
     *
     * @return static
     */
    final public function assertNoEmailSent(): self
    {
        return $this->use(function(MailerComponent $component) {
            $component->assertNoEmailSent();
        });
    }

    /**
     * @see MailerComponent::assertEmailSentTo()
     *
     * @return static
     */
    final public function assertEmailSentTo(string $expectedTo, $callback): self
    {
        return $this->use(function(MailerComponent $component) use ($expectedTo, $callback) {
            $component->assertEmailSentTo($expectedTo, $callback);
        });
    }
}

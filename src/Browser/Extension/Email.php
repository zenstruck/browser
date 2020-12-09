<?php

namespace Zenstruck\Browser\Extension;

use Zenstruck\Browser\Component\EmailComponent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait Email
{
    /**
     * @see EmailComponent::assertNoEmailSent()
     *
     * @return static
     */
    final public function assertNoEmailSent(): self
    {
        return $this->with(function(EmailComponent $component) {
            $component->assertNoEmailSent();
        });
    }

    /**
     * @see EmailComponent::assertEmailSentTo()
     *
     * @return static
     */
    final public function assertEmailSentTo(string $expectedTo, $callback): self
    {
        return $this->with(function(EmailComponent $component) use ($expectedTo, $callback) {
            $component->assertEmailSentTo($expectedTo, $callback);
        });
    }
}

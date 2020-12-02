<?php

namespace Zenstruck\Browser\Extension;

use PHPUnit\Framework\Assert as PHPUnit;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email as MailerEmail;
use Zenstruck\Browser;
use Zenstruck\Browser\Extension\Email\TestEmail;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @mixin Browser
 */
trait Email
{
    final public function assertNoEmailSent(): self
    {
        PHPUnit::assertCount(0, $this->mailerEvents(), \sprintf('Expected no email to be sent, but %d emails were sent.', \count($this->mailerEvents())));

        return $this;
    }

    /**
     * @param callable|string $callback Takes an instance of the found Email as TestEmail - if string, assume subject
     */
    final public function assertEmailSentTo(string $expectedTo, $callback): self
    {
        $events = $this->mailerEvents();

        if (0 === \count($events)) {
            PHPUnit::fail('No emails have been sent.');
        }

        if (!\is_callable($callback)) {
            $callback = static function(TestEmail $message) use ($callback) {
                $message->assertSubject($callback);
            };
        }

        $foundToAddresses = [];

        foreach ($events as $event) {
            $message = $event->getMessage();

            if (!$message instanceof MailerEmail) {
                continue;
            }

            $toAddresses = \array_map(fn(Address $address) => $address->getAddress(), $message->getTo());
            $foundToAddresses[] = $toAddresses;

            if (\in_array($expectedTo, $toAddresses, true)) {
                // address matches
                $class = $this->testEmailClass();
                $callback(new $class($message));

                return $this;
            }
        }

        PHPUnit::fail(\sprintf('Email sent, but "%s" is not among to-addresses: %s', $expectedTo, \implode(', ', \array_merge(...$foundToAddresses))));
    }

    protected function testEmailClass(): string
    {
        return TestEmail::class;
    }

    /**
     * @return MessageEvent[]
     */
    final protected function mailerEvents(): array
    {
        if (!\method_exists($this, 'profile')) {
            throw new \RuntimeException('The "Email" extension requires the "Profiler" extension.');
        }

        if (!$this->profile()->hasCollector('mailer')) {
            throw new \RuntimeException('The profiler does not include the "mailer" collector. Is symfony/mailer installed?');
        }

        return $this->profile()->getCollector('mailer')->getEvents()->getEvents();
    }
}

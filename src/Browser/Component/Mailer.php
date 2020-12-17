<?php

namespace Zenstruck\Browser\Component;

use PHPUnit\Framework\Assert as PHPUnit;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email as MailerEmail;
use Zenstruck\Browser\Component;
use Zenstruck\Browser\Component\Mailer\TestEmail;
use Zenstruck\Browser\ProfileAware;
use Zenstruck\Browser\Util\FunctionExecutor;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Mailer extends Component
{
    public function assertNoEmailSent(): self
    {
        PHPUnit::assertCount(0, $this->mailerEvents(), \sprintf('Expected no email to be sent, but %d emails were sent.', \count($this->mailerEvents())));

        return $this;
    }

    /**
     * @param callable|string $callback Takes an instance of the found Email as TestEmail - if string, assume subject
     */
    public function assertEmailSentTo(string $expectedTo, $callback): self
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

            $toAddresses = \array_map(static fn(Address $address) => $address->getAddress(), $message->getTo());
            $foundToAddresses[] = $toAddresses;

            if (\in_array($expectedTo, $toAddresses, true)) {
                // address matches
                FunctionExecutor::createFor($callback)
                    ->minArguments(1)
                    ->replaceTypedArgument(TestEmail::class, fn(string $class) => new $class($message))
                    ->execute()
                ;

                return $this;
            }
        }

        PHPUnit::fail(\sprintf('Email sent, but "%s" is not among to-addresses: %s', $expectedTo, \implode(', ', \array_merge(...$foundToAddresses))));
    }

    protected function preAssertions(): void
    {
        if (!$this->browser() instanceof ProfileAware) {
            throw new \RuntimeException(\sprintf('The "Email" component requires the browser implement %s.', ProfileAware::class));
        }
    }

    /**
     * @return MessageEvent[]
     */
    protected function mailerEvents(): array
    {
        if (!$this->browser()->profile()->hasCollector('mailer')) {
            throw new \RuntimeException('The profiler does not include the "mailer" collector. Is symfony/mailer installed?');
        }

        return $this->browser()->profile()->getCollector('mailer')->getEvents()->getEvents();
    }
}

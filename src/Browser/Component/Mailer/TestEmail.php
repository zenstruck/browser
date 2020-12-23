<?php

namespace Zenstruck\Browser\Component\Mailer;

use Symfony\Component\Mime\Email;
use Zenstruck\Browser\Assert;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @mixin Email
 */
class TestEmail
{
    private Email $email;

    final public function __construct(Email $email)
    {
        $this->email = $email;
    }

    final public function __call($name, $arguments)
    {
        return $this->email->{$name}(...$arguments);
    }

    final public function assertSubject(string $expected): self
    {
        Assert::true(
            $expected === $this->email->getSubject(),
            'Email subject "%s" does not match expected "%s".',
            $this->email->getSubject(),
            $expected
        );

        return $this;
    }

    final public function assertFrom(string $expectedEmail, string $expectedName = ''): self
    {
        foreach ($this->email->getFrom() as $address) {
            if ($expectedEmail !== $address->getAddress()) {
                continue;
            }

            Assert::true(
                $expectedName === $address->getName(),
                'From email "%s" name "%s" does not match expected "%s".',
                $address->getAddress(),
                $address->getName(),
                $expectedName
            );

            return $this;
        }

        Assert::fail('Message is not from "%s".', $expectedEmail);
    }

    final public function assertTo(string $expectedEmail, string $expectedName = ''): self
    {
        foreach ($this->email->getTo() as $address) {
            if ($expectedEmail !== $address->getAddress()) {
                continue;
            }

            Assert::true(
                $expectedName === $address->getName(),
                'To email "%s" name "%s" does not match expected "%s".',
                $address->getAddress(),
                $address->getName(),
                $expectedName
            );

            return $this;
        }

        Assert::fail('Message is not to "%s".', $expectedEmail);
    }

    final public function assertCc(string $expectedEmail, string $expectedName = ''): self
    {
        foreach ($this->email->getCc() as $address) {
            if ($expectedEmail !== $address->getAddress()) {
                continue;
            }

            Assert::true(
                $expectedName === $address->getName(),
                'CC email "%s" name "%s" does not match expected "%s".',
                $address->getAddress(),
                $address->getName(),
                $expectedName
            );

            return $this;
        }

        Assert::fail('Message is not CC\'d to "%s".', $expectedEmail);
    }

    final public function assertBcc(string $expectedEmail, string $expectedName = ''): self
    {
        foreach ($this->email->getBcc() as $address) {
            if ($expectedEmail !== $address->getAddress()) {
                continue;
            }

            Assert::true(
                $expectedName === $address->getName(),
                'BCC email "%s" name "%s" does not match expected "%s".',
                $address->getAddress(),
                $address->getName(),
                $expectedName
            );

            return $this;
        }

        Assert::fail('Message is not BCC\'d to "%s".', $expectedEmail);
    }

    final public function assertReplyTo(string $expectedEmail, string $expectedName = ''): self
    {
        foreach ($this->email->getReplyTo() as $address) {
            if ($expectedEmail !== $address->getAddress()) {
                continue;
            }

            Assert::true(
                $expectedName === $address->getName(),
                'Reply-To email "%s" name "%s" does not match expected "%s".',
                $address->getAddress(),
                $address->getName(),
                $expectedName
            );

            return $this;
        }

        Assert::fail('Message does not have "%s" as a reply-to.', $expectedEmail);
    }

    /**
     * Ensure both html and text contents contain the expected string.
     */
    final public function assertContains(string $expected): self
    {
        return $this
            ->assertHtmlContains($expected)
            ->assertTextContains($expected)
        ;
    }

    final public function assertHtmlContains(string $expected): self
    {
        Assert::true(str_contains((string) $this->email->getHtmlBody(), $expected), 'The [text/html] part does not contain "%s".', $expected);

        return $this;
    }

    final public function assertTextContains(string $expected): self
    {
        Assert::true(str_contains((string) $this->email->getTextBody(), $expected), 'The [text/plain] part does not contain "%s".', $expected);

        return $this;
    }

    final public function assertHasFile(string $expectedFilename, string $expectedContentType, string $expectedContents): self
    {
        foreach ($this->email->getAttachments() as $attachment) {
            if ($expectedFilename !== $attachment->getPreparedHeaders()->get('content-disposition')->getParameter('filename')) {
                continue;
            }

            Assert::true($expectedContents === $attachment->getBody(), 'The file "%s" does not match the expected contents.', $expectedFilename);
            Assert::true(
                $expectedContentType.'; name='.$expectedFilename === $attachment->getPreparedHeaders()->get('content-type')->getBodyAsString(),
                'The file "%s" does not match the expected content type.',
                $expectedFilename
            );

            return $this;
        }

        Assert::fail('Message does not include file with filename "%s".', $expectedFilename);
    }
}

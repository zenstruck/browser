<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Session;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\BrowserKit\Exception\BadMethodCallException;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Field\FileFormField;
use Symfony\Component\DomCrawler\Field\FormField;
use Symfony\Component\DomCrawler\Field\InputFormField;
use Symfony\Component\DomCrawler\Form;
use Zenstruck\Browser\Dom;
use Zenstruck\Browser\Dom\Exception\RuntimeException;
use Zenstruck\Browser\Dom\Node;
use Zenstruck\Browser\Dom\Node\Form\Button;
use Zenstruck\Browser\Dom\Node\Form\Element;
use Zenstruck\Browser\Dom\Node\Form\Field;
use Zenstruck\Browser\Dom\Node\Form\Field\Checkbox;
use Zenstruck\Browser\Dom\Node\Form\Field\File;
use Zenstruck\Browser\Dom\Node\Form\Field\Input;
use Zenstruck\Browser\Dom\Node\Form\Field\Radio;
use Zenstruck\Browser\Dom\Node\Form\Field\Select\Combobox;
use Zenstruck\Browser\Dom\Node\Form\Field\Select\Multiselect;
use Zenstruck\Browser\Dom\Node\Form\Field\Select\Option;
use Zenstruck\Browser\Dom\Node\Form\Field\Textarea;
use Zenstruck\Browser\Session;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * @internal
 *
 * @method KernelBrowser client()
 */
final class KernelSession extends Session
{
    private Dom $dom;
    private ?Response $response = null;

    /** @var array<string,Form> */
    private array $forms = [];

    public function __construct(KernelBrowser $client)
    {
        parent::__construct($client);
    }

    public function dom(): Dom
    {
        try {
            return $this->resetIfRequired()->dom ??= new Dom($this->client()->getCrawler(), $this);
        } catch (BadMethodCallException) {
            throw new RuntimeException('The session has not yet been started.');
        }
    }

    public function content(): string
    {
        return $this->client()->getInternalResponse()->getContent();
    }

    public function currentUrl(): string
    {
        return $this->client()->getInternalRequest()->getUri();
    }

    public function isSuccess(): bool
    {
        return $this->statusCode() >= 200 && $this->statusCode() < 300;
    }

    public function isRedirect(): bool
    {
        return $this->statusCode() >= 300 && $this->statusCode() < 400;
    }

    public function statusCode(): int
    {
        return $this->client()->getInternalResponse()->getStatusCode();
    }

    public function isStarted(): bool
    {
        try {
            $this->client()->getInternalResponse();

            return true;
        } catch (BadMethodCallException) {
            return false;
        }
    }

    public function click(Node $node): void
    {
        $this->ensureSameSession();

        if ('a' === \mb_strtolower($node->tag())) {
            $this->client()->click($node->crawler()->link());

            return;
        }

        if (!$node instanceof Button) {
            throw new RuntimeException(\sprintf('"%s" only supports clicking on "a" tags or buttons.', __CLASS__));
        }

        $form = $this->formFor($node);

        if ($node->name()) {
            // add button to form
            $field = new InputFormField($node->element());
            $field->setValue($node->value());
            $form->set($field);
        }

        $this->client()->submit($form);
    }

    public function select(Option|Radio|Checkbox $node): void
    {
        if ($node instanceof Checkbox || $node instanceof Radio) {
            $field = $this->formFieldFor($node);

            \assert($field instanceof ChoiceFormField);

            $node instanceof Checkbox ? $field->tick() : $field->select($node->value());

            return;
        }

        $select = $node->selector() ?? throw new RuntimeException('Could not find "select" for "option".');
        $field = $this->formFieldFor($select);

        \assert($field instanceof ChoiceFormField);

        if ($select instanceof Combobox) {
            $field->select($node->value());

            return;
        }

        $values = (array) $field->getValue();
        $values[] = $node->value();

        $field->select(\array_unique($values));
    }

    public function unselect(Checkbox|Multiselect $node): void
    {
        $field = $this->formFieldFor($node);

        \assert($field instanceof ChoiceFormField);

        if ($field->isMultiple()) {
            $field->select([]);

            return;
        }

        $field->untick();
    }

    public function attach(File $node, array $filenames): void
    {
        $form = $this->formFor($node);
        $field = $this->formFieldFor($node);

        $field->setValue(\array_shift($filenames));

        foreach ($filenames as $file) {
            $field = new FileFormField($node->element());
            $field->upload($file);
            $form->set($field);
        }
    }

    public function fill(Textarea|Input $node, string $value): void
    {
        $this->formFieldFor($node)->setValue($value);
    }

    private function ensureSameSession(): void
    {
        if (!$this->isSameSession()) {
            throw new RuntimeException('The current session has expired.');
        }
    }

    private function resetIfRequired(): self
    {
        if ($this->isSameSession()) {
            return $this;
        }

        $this->response = $this->client()->getInternalResponse();
        unset($this->dom);
        $this->forms = [];

        return $this;
    }

    private function isSameSession(): bool
    {
        try {
            return $this->response === $this->client()->getInternalResponse();
        } catch (BadMethodCallException) {
            throw new RuntimeException('The session has not yet been started.');
        }
    }

    private function formFieldFor(Field $node): FormField
    {
        $form = $this->formFor($node);
        $name = \str_replace('[]', '', (string) $node->name());
        $field = $form->get($name);

        if (\is_array($field)) {
            return $field[$this->fieldPositionFor($node)]; // @phpstan-ignore-line
        }

        return $field;
    }

    private function fieldPositionFor(Field $node): int
    {
        $element = $node->element();

        foreach ($node->collection() as $position => $field) {
            if ($field->element()->getNodePath() === $element->getNodePath()) {
                return $position;
            }
        }

        return 0;
    }

    private function formFor(Element $node): Form
    {
        $this->ensureSameSession();

        $form = $node->form() ?? throw new RuntimeException(\sprintf('Unable to find form for "%s".', $node::class));
        $element = $form->element();
        $id = \md5($element->getLineNo().$element->getNodePath().$element->nodeValue);

        return $this->forms[$id] ??= $form->crawler()->form();
    }
}

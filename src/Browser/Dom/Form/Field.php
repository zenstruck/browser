<?php

namespace Zenstruck\Browser\Dom\Form;

use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Field\FileFormField;
use Symfony\Component\DomCrawler\Field\FormField;
use Symfony\Component\DomCrawler\Form;
use Zenstruck\Browser\Dom;
use Zenstruck\Browser\Dom\Form\Field\ChoiceField;
use Zenstruck\Browser\Dom\Form\Field\FileField;
use Zenstruck\Browser\Dom\Form\Field\InputField;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class Field
{
    protected FormField $inner;
    protected Dom $dom;
    protected Form $form;

    public function __construct(FormField $inner, Dom $dom, Form $form)
    {
        $this->inner = $inner;
        $this->dom = $dom;
        $this->form = $form;
    }

    public function __call(string $name, array $arguments)
    {
        if (!\method_exists($this->inner, $name)) {
            throw new \BadMethodCallException(\sprintf('Method "%s" does not exist on "%s".', $name, \get_class($this->inner)));
        }

        return $this->inner->{$name}(...$arguments);
    }

    public static function create(Dom $dom): self
    {
        $form = $dom->form();
        $field = $form->get(\str_replace('[]', '', $dom->attr('name')));

        if (\is_array($field) && isset($field[0]) && $field[0] instanceof FileFormField) {
            $field = $field[0];
        }

        if ($field instanceof FileFormField) {
            return new FileField($field, $dom, $form);
        }

        if ($field instanceof ChoiceFormField) {
            return new ChoiceField($field, $dom, $form);
        }

        return new InputField($field, $dom, $form);
    }

    public function attr(string $name): ?string
    {
        return $this->dom->attr($name);
    }
}

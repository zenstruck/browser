<?php

namespace Zenstruck\Browser\Dom\Assertion;

use Zenstruck\Assert;
use Zenstruck\Assert\Assertion\ComparisonAssertion;
use Zenstruck\Assert\Assertion\Negatable;
use Zenstruck\Assert\AssertionFailed;
use Zenstruck\Browser\Dom;
use Zenstruck\Browser\Dom\Form\Field\ChoiceField;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class FieldCheckedAssertion implements Negatable
{
    private ChoiceField $field;
    private array $context;

    public function __construct(Dom $dom, string $selector)
    {
        $field = Assert::try(fn() => $dom->getFormField($selector));

        if (!$field instanceof ChoiceField || !$field->is(['radio', 'checkbox'])) {
            Assert::fail('Expected element matching "{selector}" to be a checkbox or radio.', ['selector' => $selector]);
        }

        $this->field = $field;
        $this->context = ['selector' => $selector];
    }

    public function __invoke(): void
    {
        if ($this->field->is('checkbox')) {
            // todo truthy assertion object in zenstruck/assert
            if (!$this->field->hasValue()) {
                AssertionFailed::throw('Expected checkbox matching "{selector}" to be checked.', $this->context);
            }

            return;
        }

        // todo wrapper assertion object in zenstruck/assert
        ComparisonAssertion::same(
            $this->field->getValue(),
            $this->field->attr('value'),
            'Expected radio matching "{selector}" to be selected.',
            $this->context
        )();
    }

    public function notFailure(): AssertionFailed
    {
        return new AssertionFailed(
            $this->field->is('checkbox') ? 'Expected checkbox matching "{selector}" to not be checked.' : 'Expected radio matching "{selector}" to not be selected.',
            $this->context
        );
    }
}

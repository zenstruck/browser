<?php

namespace Zenstruck\Browser\Dom\Assertion;

use Zenstruck\Assert;
use Zenstruck\Assert\Assertion\ComparisonAssertion;
use Zenstruck\Assert\Assertion\ContainsAssertion;
use Zenstruck\Assert\Assertion\Negatable;
use Zenstruck\Assert\AssertionFailed;
use Zenstruck\Browser\Dom;
use Zenstruck\Browser\Dom\Form\Field\ChoiceField;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class FieldSelectedAssertion implements Negatable
{
    private ChoiceField $field;
    private string $expected;
    private array $context;

    public function __construct(Dom $dom, string $selector, string $expected)
    {
        $field = Assert::try(fn() => $dom->getFormField($selector));

        if (!$field instanceof ChoiceField || !$field->is(['radio', 'select'])) {
            Assert::fail('Expected element matching "{selector}" to be a select or radio.', ['selector' => $selector]);
        }

        $this->field = $field;
        $this->expected = $expected;
        $this->context = ['selector' => $selector, 'needle' => $expected, 'expected' => $this->expected];
    }

    public function __invoke(): void
    {
        if ($this->field->isMultiple()) {
            (new ContainsAssertion(
                $this->expected,
                $this->field->getValue(),
                'Expected multi-select matching "{selector}" to have "{needle}" selected.',
                \array_merge($this->context, [
                    'compare_expected' => [$this->expected],
                    'compare_actual' => $this->field->getValue(),
                ])
            ))();

            return;
        }

        ComparisonAssertion::same(
            $this->field->getValue(),
            $this->expected,
            \sprintf('Expected %s matching "{selector}" to have "{expected}" selected.', $this->field->getType()),
            $this->context
        )();
    }

    public function notFailure(): AssertionFailed
    {
        return new AssertionFailed(
            $this->field->isMultiple() ? 'Expected multi-select matching "{selector}" to not have "{needle}" selected.' : \sprintf('Expected %s matching "{selector}" to not have "{expected}" selected.', $this->field->getType()),
            $this->context
        );
    }
}

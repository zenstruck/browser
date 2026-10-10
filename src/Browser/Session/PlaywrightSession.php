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

use Playwright\Exception\PlaywrightException;
use Playwright\Locator\LocatorInterface;
use Playwright\Page\PageInterface;
use Playwright\Symfony\Client\PlaywrightKernelClient;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Browser\Session;
use Zenstruck\Dom;
use Zenstruck\Dom\Exception\RuntimeException;
use Zenstruck\Dom\Modifier;
use Zenstruck\Dom\Node;
use Zenstruck\Dom\Node\Form\Field\Checkbox;
use Zenstruck\Dom\Node\Form\Field\File;
use Zenstruck\Dom\Node\Form\Field\Input;
use Zenstruck\Dom\Node\Form\Field\Radio;
use Zenstruck\Dom\Node\Form\Field\Select\Combobox;
use Zenstruck\Dom\Node\Form\Field\Select\Multiselect;
use Zenstruck\Dom\Node\Form\Field\Select\Option;
use Zenstruck\Dom\Node\Form\Field\Textarea;
use Zenstruck\Dom\RenderedSession;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 *
 * @method PlaywrightKernelClient client()
 */
final class PlaywrightSession extends Session implements RenderedSession
{
    private ?string $downloadedContent = null;

    public function __construct(PlaywrightKernelClient $client)
    {
        parent::__construct($client);
    }

    public function dom(): Dom
    {
        return new Dom($this->client()->getCrawler(), $this);
    }

    public function content(): string
    {
        return $this->downloadedContent ?? (string) $this->page()->content();
    }

    /**
     * The kernel's response, which is what carries an exception the browser only renders as text.
     */
    public function rawContent(): string
    {
        $response = $this->client()->getLastSymfonyResponse();

        if (!$response || false === $body = $response->getContent()) {
            return $this->content();
        }

        return $body;
    }

    public function currentUrl(): string
    {
        return $this->page()->url();
    }

    public function statusCode(): int
    {
        return $this->response()->getStatusCode();
    }

    public function responseHeader(string $header): ?string
    {
        return $this->response()->headers->get($header);
    }

    public function responseHeaders(): array
    {
        return $this->response()->headers->all();
    }

    public function isStarted(): bool
    {
        return null !== $this->client()->getLastSymfonyResponse();
    }

    public function visit(string $uri): void
    {
        $this->downloadedContent = null;

        try {
            // not client()->request(), which rebuilds the url from its parts and drops the fragment
            $this->client()->visit($this->toPath($uri));
        } catch (PlaywrightException $e) {
            if (!\str_contains($e->getMessage(), 'Download is starting')) {
                throw $e;
            }

            // the browser downloads attachments instead of rendering them, leaving the page where
            // it was. The kernel already built the body on this side of the bridge, so serve that
            $this->downloadedContent = $this->rawContent();
        }
    }

    public function click(Node $node, Modifier ...$modifiers): void
    {
        $this->downloadedContent = null;

        $this->locatorFor($node)->click(self::modifierOptions($modifiers));
        $this->page()->waitForLoadState();
    }

    public function doubleClick(Node $node, Modifier ...$modifiers): void
    {
        $this->downloadedContent = null;

        $before = $this->page()->url();

        $this->locatorFor($node)->dblclick(self::modifierOptions($modifiers));

        $this->awaitPossibleNavigation($before);
    }

    public function rightClick(Node $node, Modifier ...$modifiers): void
    {
        $this->downloadedContent = null;

        $before = $this->page()->url();

        $this->locatorFor($node)->click(['button' => 'right', ...self::modifierOptions($modifiers)]);

        $this->awaitPossibleNavigation($before);
    }

    public function hover(Node $node, Modifier ...$modifiers): void
    {
        $this->locatorFor($node)->hover(self::modifierOptions($modifiers));
    }

    public function focus(Node $node): void
    {
        $this->locatorFor($node)->focus();
    }

    public function blur(Node $node): void
    {
        $this->locatorFor($node)->blur();
    }

    public function press(string ...$keys): void
    {
        $this->downloadedContent = null;

        $before = $this->page()->url();

        foreach ($keys as $key) {
            $this->page()->keyboard()->press($key);
        }

        $this->awaitPossibleNavigation($before);
    }

    public function select(Option|Radio|Checkbox $node): void
    {
        if ($node instanceof Checkbox || $node instanceof Radio) {
            $this->locatorFor($node)->check();

            return;
        }

        $select = $node->selector() ?? throw new RuntimeException('Could not find "select" for "option".');
        $locator = $this->locatorFor($select);

        if ($select instanceof Combobox) {
            $this->selectOption($locator, $node->value());

            return;
        }

        // a multiselect keeps what is already selected
        $current = \array_filter((array) $locator->evaluate('el => Array.from(el.selectedOptions).map(o => o.value)'), 'is_string');

        $locator->selectOption(\array_values(\array_unique([...$current, $node->value()])));
    }

    public function unselect(Checkbox|Multiselect $node): void
    {
        $locator = $this->locatorFor($node);

        if ($node instanceof Multiselect) {
            $locator->evaluate('el => { el.selectedIndex = -1; el.dispatchEvent(new Event("change", {bubbles: true})); }');

            return;
        }

        $locator->uncheck();
    }

    public function attach(File $node, array $filenames): void
    {
        $locator = $this->locatorFor($node);

        if (\count($filenames) > 1 && null === $locator->getAttribute('multiple')) {
            throw new \InvalidArgumentException('Cannot attach multiple files to a non-multiple file field.');
        }

        $locator->setInputFiles(\array_values($filenames));
    }

    public function fill(Textarea|Input $node, string $value): void
    {
        $this->locatorFor($node)->fill($value);
    }

    public function value(Input|Textarea $node): string
    {
        return $this->locatorFor($node)->inputValue();
    }

    public function isSelected(Checkbox|Radio|Option $node): bool
    {
        if ($node instanceof Option) {
            return (bool) $this->locatorFor($node)->evaluate('el => el.selected');
        }

        return $this->locatorFor($node)->isChecked();
    }

    public function text(Node $node): string
    {
        // innerText falls back to textContent for elements that are not rendered, but a user only
        // sees what is visible. <title> and <option> are the exceptions: neither is ever rendered
        // on its own, yet both carry text the caller is asking about
        return (string) $this->locatorFor($node)->evaluate(
            "el => ['TITLE', 'OPTION'].includes(el.tagName) ? el.textContent : (el.checkVisibility() ? el.innerText : '')",
        );
    }

    public function isVisible(Node $node): bool
    {
        return $this->locatorFor($node)->isVisible();
    }

    public function page(): PageInterface
    {
        return $this->client()->getPage() ?? throw new RuntimeException('Unable to access the page before visiting a url.');
    }

    /**
     * A dblclick/contextmenu handler or a key press may navigate, which Playwright does not wait
     * for. Give that a brief chance to happen before reporting the page as settled.
     */
    private function awaitPossibleNavigation(string $before): void
    {
        try {
            $this->page()->waitForFunction('url => location.href !== url', $before, ['timeout' => 1000]);
        } catch (PlaywrightException) {
            // nothing navigated
        }

        $this->page()->waitForLoadState();
    }

    private function selectOption(LocatorInterface $locator, string $value): void
    {
        try {
            $locator->selectOption($value);
        } catch (\Throwable) {
            // try selecting by visible text
            $locator->selectOption(['label' => $value]);
        }
    }

    private function toPath(string $url): string
    {
        if (!\preg_match('#^https?://#', $url)) {
            return $url;
        }

        return (string) \preg_replace('#^https?://[^/]+#', '', $url) ?: '/';
    }

    private function locatorFor(Node $node): LocatorInterface
    {
        return $this->page()->locator('xpath='.$node->element()->getNodePath());
    }

    /**
     * @param Modifier[] $modifiers
     *
     * @return array{modifiers?: list<string>}
     */
    private static function modifierOptions(array $modifiers): array
    {
        if (!$modifiers) {
            return [];
        }

        return ['modifiers' => \array_map(static fn(Modifier $modifier) => $modifier->value, \array_values($modifiers))];
    }

    private function response(): Response
    {
        return $this->client()->getLastSymfonyResponse() ?? throw new RuntimeException('No response available.');
    }
}

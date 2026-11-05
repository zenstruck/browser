<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Tests\Fixture;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pages exercising everything Turbo can do to a navigation. They are plain html that also
 * works without Turbo, except for frames and streams.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class NavigationController
{
    public function index(): Response
    {
        return self::page('navigation index', self::indexBody());
    }

    public function target(Request $request): Response
    {
        $query = \htmlspecialchars(\http_build_query($request->query->all()));

        return self::page('navigation target', <<<HTML
            <p id="query">{$query}</p>
            <div style="height: 3000px"></div>
            <h2 id="section">section</h2>
            HTML);
    }

    public function redirect(): Response
    {
        return new RedirectResponse('/nav/target?redirected=1');
    }

    public function search(Request $request): Response
    {
        $term = \htmlspecialchars($request->query->getString('q'));

        return self::page('search results', "<p id=\"results\">results for: {$term}</p>");
    }

    public function create(Request $request): Response
    {
        $title = $request->request->getString('title');

        if ('' === $title) {
            return self::page('navigation index', self::indexBody('<p id="error">title is required</p>'), 422);
        }

        return new RedirectResponse('/nav/target?'.\http_build_query(['created' => $title]), 303);
    }

    public function reload(): Response
    {
        return self::page('reloaded page', '', '<meta name="turbo-visit-control" content="reload">');
    }

    public function delete(): Response
    {
        return new RedirectResponse('/nav/target?deleted=1', 303);
    }

    public function morph(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $request->getSession()->set('morph_name', $request->request->getString('name'));

            // redirecting back to the same url is what makes Turbo refresh (and morph) the page
            return new RedirectResponse('/nav/morph', 303);
        }

        $name = \htmlspecialchars($request->getSession()->get('morph_name', '(none)'));

        return self::page('morph page', <<<HTML
            <p id="name">name: {$name}</p>
            <form action="/nav/morph" method="post">
                <label for="name-input">Name</label>
                <input id="name-input" name="name" type="text">
                <button>save name</button>
            </form>
            HTML, '<meta name="turbo-refresh-method" content="morph">');
    }

    public function frames(): Response
    {
        return self::page('frames', <<<HTML
            <p>outside frame</p>
            <turbo-frame id="frame1">
                <a href="/nav/frames/content">frame link</a>
            </turbo-frame>
            <a href="/nav/frames/content" data-turbo-frame="frame1">outside link targeting frame</a>
            <turbo-frame id="top-frame">
                <a href="/nav/target" data-turbo-frame="_top">top link</a>
            </turbo-frame>
            <turbo-frame id="advance-frame" data-turbo-action="advance">
                <a href="/nav/frames/content">advancing frame link</a>
            </turbo-frame>
            <turbo-frame id="missing-frame">
                <a href="/nav/target">missing frame link</a>
            </turbo-frame>
            <turbo-frame id="form-frame">
                <form action="/nav/frames/form" method="post">
                    <label for="frame-title">Frame Title</label>
                    <input id="frame-title" name="title" type="text">
                    <button>save in frame</button>
                </form>
            </turbo-frame>
            <turbo-frame id="eager-frame" src="/nav/frames/content">eager frame loading</turbo-frame>
            <div style="height: 3000px"></div>
            <turbo-frame id="lazy-frame" src="/nav/frames/content" loading="lazy">lazy frame loading</turbo-frame>
            HTML);
    }

    public function frameContent(Request $request): Response
    {
        $title = \htmlspecialchars($request->query->getString('title'));
        $frames = \implode("\n", \array_map(
            static fn(string $id) => "<turbo-frame id=\"{$id}\">{$id} content loaded {$title}</turbo-frame>",
            ['frame1', 'advance-frame', 'form-frame', 'eager-frame', 'lazy-frame'],
        ));

        return self::page('frame content', "<p>outside of the frames</p>\n{$frames}");
    }

    public function frameForm(Request $request): Response
    {
        return new RedirectResponse('/nav/frames/content?'.\http_build_query(['title' => $request->request->getString('title')]), 303);
    }

    public function streams(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return self::stream($request);
        }

        $refreshes = $request->getSession()->get('stream_refreshes', 0);
        $buttons = \implode("\n", \array_map(
            static fn(string $action) => "<button name=\"stream\" value=\"{$action}\">{$action} stream</button>",
            ['append', 'prepend', 'replace', 'update', 'remove', 'before', 'after', 'refresh'],
        ));

        return self::page('streams', <<<HTML
            <ul id="items">
                <li id="item-1">item 1</li>
                <li id="item-2">item 2</li>
            </ul>
            <div id="message">original message</div>
            <p id="refreshes">refreshes: {$refreshes}</p>
            <form action="/nav/streams" method="post">
                {$buttons}
            </form>
            <button id="inline-stream" onclick="document.body.insertAdjacentHTML('beforeend', '<turbo-stream action=&quot;update&quot; target=&quot;message&quot;><template>inline message</template></turbo-stream>')">inline stream</button>
            HTML);
    }

    private static function stream(Request $request): Response
    {
        $action = $request->request->getString('stream');

        if ('refresh' === $action) {
            $session = $request->getSession();
            $session->set('stream_refreshes', $session->get('stream_refreshes', 0) + 1);
        }

        $stream = match ($action) {
            'append' => '<turbo-stream action="append" target="items"><template><li>appended item</li></template></turbo-stream>',
            'prepend' => '<turbo-stream action="prepend" target="items"><template><li>prepended item</li></template></turbo-stream>',
            'replace' => '<turbo-stream action="replace" target="item-1"><template><li id="item-1">replaced item</li></template></turbo-stream>',
            'update' => '<turbo-stream action="update" target="message"><template>updated message</template></turbo-stream>',
            'remove' => '<turbo-stream action="remove" target="item-2"></turbo-stream>',
            'before' => '<turbo-stream action="before" target="item-1"><template><li>before item</li></template></turbo-stream>',
            'after' => '<turbo-stream action="after" target="item-1"><template><li>after item</li></template></turbo-stream>',
            'refresh' => '<turbo-stream action="refresh"></turbo-stream>',
        };

        return new Response($stream, 200, ['Content-Type' => 'text/vnd.turbo-stream.html']);
    }

    private static function indexBody(string $error = ''): string
    {
        return <<<HTML
            <p id="turbo-status">turbo inactive</p>
            <script>if (window.Turbo) document.getElementById('turbo-status').textContent = 'turbo active'</script>
            <a href="/nav/target">advance link</a>
            <a href="/nav/target" data-turbo-action="replace">replace link</a>
            <a href="/nav/target#section">fragment link</a>
            <a href="#local">anchor link</a>
            <a href="/nav/redirect">redirect link</a>
            <a href="/nav/target" data-turbo="false">opt out link</a>
            <a href="/zip">download link</a>
            <a href="/nav/reload">reload link</a>
            <a href="/nav/delete" data-turbo-method="delete">delete link</a>
            <a href="/nav/target" data-turbo-confirm="Are you sure?">confirm link</a>
            <form action="/nav/search" method="get">
                <label for="q">Search</label>
                <input id="q" name="q" type="text">
                <button>search</button>
            </form>
            <form action="/nav/create" method="post">
                {$error}
                <label for="title">Title</label>
                <input id="title" name="title" type="text">
                <button>create</button>
            </form>
            <div style="height: 3000px"></div>
            <h2 id="local">local anchor</h2>
            HTML;
    }

    private static function page(string $title, string $body, string $head = '', int $status = 200): Response
    {
        return new Response(<<<HTML
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <title>{$title}</title>
                {$head}
            </head>
            <body>
                <h1>{$title}</h1>
                <label for="permanent">Permanent</label>
                <input id="permanent" name="permanent" type="text" data-turbo-permanent>
                {$body}
            </body>
            </html>
            HTML, $status);
    }
}

<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Tests;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Zenstruck\Browser\HtmlBrowser;
use Zenstruck\Browser\HttpOptions;
use Zenstruck\Browser\Json;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Browser\Test\LegacyExtension;
use Zenstruck\Browser\Tests\Fixture\AbstractApiBrowser;
use Zenstruck\Browser\Tests\Fixture\CustomApiBrowser;
use Zenstruck\Browser\Tests\Fixture\CustomHtmlBrowser;
use Zenstruck\Browser\Tests\Fixture\LegacyKernelBrowser;
use Zenstruck\Browser\Tests\Fixture\TestComponent1;
use Zenstruck\Callback\Exception\UnresolveableArgument;

final class ApiBrowserTest extends KernelTestCase
{
    use HasBrowser;

    /**
     * @test
     */
    #[Test]
    public function switches_preserve_the_response_without_making_a_request(): void
    {
        $html = $this->htmlBrowser()->visit('/page1');
        $request = $html->client()->getRequest();
        $response = $html->client()->getResponse();
        $api = $html->api()->assertOn('/page1')->assertSuccessful();

        $this->assertInstanceOf(HtmlBrowser::class, $html);
        $this->assertSame($html->client(), $api->client());
        $this->assertSame($request, $api->client()->getRequest());
        $this->assertSame($response, $api->client()->getResponse());
        $this->assertSame($html, $api->html());
        $this->assertSame($api, $html->api());
        $this->assertSame($api, $api->api());
        $this->assertSame($html, $html->html());

        $api->get('/page2')->html()->assertSee('success');
    }

    /**
     * @test
     */
    #[Test]
    public function an_api_browser_can_create_its_html_view_before_making_a_request(): void
    {
        $api = $this->apiBrowser();
        $html = $api->html()->visit('/page1')->assertSee('h1 title');

        $this->assertSame($api, $html->api());
        $this->assertSame($html, $api->html());
    }

    /**
     * @test
     */
    #[Test]
    public function api_defaults_survive_switching_and_do_not_apply_to_html_navigation(): void
    {
        $api = $this->apiBrowser()->setDefaultHttpOptions(['headers' => ['X-Token' => 'secret']]);

        $api->get('/http-method')->assertJsonMatches('headers."x-token"', ['secret']);
        $api->html()->visit('/http-method')->assertNotContains('x-token');
        $api->html()->api()->get('/http-method')->assertJsonMatches('headers."x-token"', ['secret']);
    }

    /**
     * @test
     */
    #[Test]
    public function form_authentication_is_shared_with_the_api_view(): void
    {
        $html = $this->htmlBrowser()
            ->visit('/login')
            ->fillField('Username', 'kevin')
            ->fillField('Password', 'pass')
            ->click('Log in')
        ;

        $html->api()->get('/user')->assertAuthenticated('kevin')->assertContains('user: kevin/pass/');
        $html->visit('/user')->assertAuthenticated('kevin');
    }

    /**
     * @test
     */
    #[Test]
    public function acting_as_and_session_callbacks_work_across_views(): void
    {
        $api = $this->apiBrowser()->actingAs(new InMemoryUser('kevin', 'pass'));

        $api->html()->visit('/user')->assertAuthenticated('kevin');
        $api->use(static function(SessionInterface $session) {
            $session->set('key', 'shared');
        });
        $api->html()->visit('/read-session')->assertContains('shared');
        $api->get('/read-session')->assertContains('shared');
    }

    /**
     * @test
     */
    #[Test]
    public function switching_preserves_redirect_and_exception_settings(): void
    {
        $html = $this->htmlBrowser()->interceptRedirects()->throwExceptions()->disableReboot();
        $api = $html->api()->get('/redirect1')->assertStatus(302)->assertRedirected();

        $api->html()->api()->get('/redirect1')->assertStatus(302);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('exception thrown');

        $api->get('/exception');
    }

    /**
     * @test
     */
    #[Test]
    public function switching_preserves_exception_expectations_and_invalidates_stale_responses(): void
    {
        $html = $this->htmlBrowser()->visit('/page1')->expectException(\RuntimeException::class);
        $api = $html->api()->get('/exception');

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The last request threw the expected exception');

        $api->html()->assertSuccessful();
    }

    /**
     * @test
     */
    #[Test]
    public function reboot_controls_apply_across_views(): void
    {
        $html = $this->htmlBrowser()->disableReboot()->visit('/page1');
        $container = $html->client()->getContainer();
        $api = $html->api()->get('/page2');

        $this->assertSame($container, $api->client()->getContainer());

        $api->enableReboot()->html()->visit('/page1');

        $this->assertNotSame($container, $html->client()->getContainer());
    }

    /**
     * @test
     */
    #[Test]
    public function api_factory_accepts_kernel_and_server_options(): void
    {
        $this->apiBrowser(['debug' => false], ['HTTP_X_FACTORY' => 'configured'])
            ->get('/http-method')
            ->assertJsonMatches('headers."x-factory"', ['configured'])
        ;

        $this->assertFalse(static::$kernel->isDebug());
    }

    /**
     * @test
     */
    #[Test]
    public function fresh_factories_create_independent_clients(): void
    {
        $first = $this->apiBrowser()->actingAs(new InMemoryUser('kevin', 'pass'));
        $second = $this->apiBrowser()->get('/user')->assertNotAuthenticated();

        $this->assertNotSame($first->client(), $second->client());

        $first->get('/user')->assertAuthenticated('kevin');
    }

    /**
     * @test
     */
    #[Test]
    public function configured_api_class_is_used_by_factories_switches_and_callbacks(): void
    {
        $previous = $_SERVER['API_BROWSER_CLASS'] ?? null;
        $_SERVER['API_BROWSER_CLASS'] = CustomApiBrowser::class;

        try {
            $this->assertInstanceOf(CustomApiBrowser::class, $this->apiBrowser());

            $this->htmlBrowser()->api()->use(static function(CustomApiBrowser $browser) {
                $browser->get('/page2')->assertContains('success');
            });
        } finally {
            $this->restoreVariable('API_BROWSER_CLASS', $previous);
        }
    }

    /**
     * @test
     *
     * @dataProvider invalidApiClasses
     */
    #[Test]
    #[DataProvider('invalidApiClasses')]
    public function invalid_api_classes_are_rejected(string $class, bool $switch): void
    {
        $previous = $_SERVER['API_BROWSER_CLASS'] ?? null;
        $_SERVER['API_BROWSER_CLASS'] = $class;

        try {
            $this->expectException(\LogicException::class);
            $this->expectExceptionMessage('"API_BROWSER_CLASS" env variable must reference a concrete class');

            if ($switch) {
                $this->htmlBrowser()->api();

                return;
            }

            $this->apiBrowser();
        } finally {
            $this->restoreVariable('API_BROWSER_CLASS', $previous);
        }
    }

    public static function invalidApiClasses(): iterable
    {
        foreach ([HtmlBrowser::class, AbstractApiBrowser::class, 'MissingApiBrowser'] as $class) {
            yield $class.' factory' => [$class, false];
            yield $class.' switch' => [$class, true];
        }
    }

    /**
     * @test
     */
    #[Test]
    public function switching_back_preserves_the_custom_html_browser(): void
    {
        $previous = $_SERVER['KERNEL_BROWSER_CLASS'] ?? null;
        $_SERVER['KERNEL_BROWSER_CLASS'] = CustomHtmlBrowser::class;

        try {
            $html = $this->htmlBrowser();

            $this->assertInstanceOf(CustomHtmlBrowser::class, $html);
            $this->assertSame($html, $html->api()->html());

            $html->api()->html()->homepage()->assertOn('/page1');

            $this->assertInstanceOf(CustomHtmlBrowser::class, $this->apiBrowser()->html());
        } finally {
            $this->restoreVariable('KERNEL_BROWSER_CLASS', $previous);
        }
    }

    /**
     * @test
     */
    #[Test]
    public function legacy_subclasses_keep_their_overrides_defaults_and_identity(): void
    {
        $previous = $_SERVER['KERNEL_BROWSER_CLASS'] ?? null;
        $_SERVER['KERNEL_BROWSER_CLASS'] = LegacyKernelBrowser::class;

        try {
            $deprecations = $this->captureDeprecations(function() {
                $html = $this->htmlBrowser()->visit('/page1')->assertSee('h1 title');

                $this->assertSame($html, $html->post('/http-method'));

                $html->assertContains('"x-legacy":["yes"]');
                $html->configureDefaults()->api()->get('/http-method')->assertJsonMatches('headers."x-legacy"', ['configured']);

                $this->assertSame($html, $html->api()->html());
            });

            $this->assertCount(2, $deprecations);
            $this->assertStringContainsString('extend Zenstruck\\Browser\\HtmlBrowser instead', $deprecations[0]);
            $this->assertStringContainsString('->api()->post()', $deprecations[1]);
        } finally {
            $this->restoreVariable('KERNEL_BROWSER_CLASS', $previous);
        }
    }

    /**
     * @test
     */
    #[Test]
    public function legacy_json_helpers_and_injection_emit_one_deprecation_per_call(): void
    {
        $html = $this->htmlBrowser();
        $html->api()->post('/json', ['json' => ['foo' => 'bar']]);

        $deprecations = $this->captureDeprecations(function() use ($html) {
            $this->assertSame($html, $html->setDefaultHttpOptions(HttpOptions::json()));
            $this->assertSame($html, $html->assertJson());
            $this->assertSame($html, $html->assertJsonMatches('foo', 'bar'));
            $this->assertSame(['foo' => 'bar'], $html->json()->decoded());

            $html->use(static function(Json $json) {
                $json->assertMatches('foo', 'bar');
            });
        });

        $this->assertCount(5, $deprecations);
    }

    /**
     * @test
     */
    #[Test]
    public function api_methods_do_not_emit_migration_deprecations(): void
    {
        $deprecations = $this->captureDeprecations(function() {
            $api = $this->apiBrowser()
                ->setDefaultHttpOptions(HttpOptions::json())
                ->post('/json', ['json' => ['foo' => 'bar']])
                ->assertJson()
                ->assertJsonMatches('foo', 'bar')
            ;

            $api->use(static function(Json $json) {
                $json->assertMatches('foo', 'bar');
            });
            $api->get('/xml')->assertXml()->html()->visit('/page1')->assertHtml();
        });

        $this->assertSame([], $deprecations);
    }

    /**
     * @test
     */
    #[Test]
    public function api_browsers_expose_xml_crawlers_but_no_html_operations(): void
    {
        $api = $this->apiBrowser()->get('/xml')->assertXml();

        $api->use(function(Crawler $crawler) {
            $this->assertCount(2, $crawler->filter('url loc'));
        });

        foreach (['visit', 'click', 'fillField', 'assertSee', 'assertSeeElement', 'assertHtml'] as $method) {
            $this->assertFalse(\method_exists($api, $method));
        }

        $this->expectException(UnresolveableArgument::class);

        $api->use(static function(TestComponent1 $component) {});
    }

    /**
     * @test
     */
    #[Test]
    public function failure_capture_uses_the_active_view_once_and_keeps_shared_artifacts(): void
    {
        $extension = new LegacyExtension();
        $extension->executeBeforeFirstTest();
        $name = 'api_'.\bin2hex(\random_bytes(4));
        $extension->executeBeforeTest($name);
        $html = $this->htmlBrowser()->visit('/page1');
        $api = $html->api()->get('/http-method');
        $api->html()->api()->saveSource($name.'.txt');

        $this->assertSame($html->savedArtifacts(), $api->savedArtifacts());

        $extension->executeAfterTestFailure($name, 'failure', 0.0);
        $path = __DIR__.'/../var/browser/source/failure_'.$name.'__0.json';

        $this->assertFileExists($path);
        $this->assertSame('GET', \json_decode(\file_get_contents($path), true, 512, \JSON_THROW_ON_ERROR)['method']);
        $this->assertFileDoesNotExist(__DIR__.'/../var/browser/source/failure_'.$name.'__1.json');
        $this->assertFileDoesNotExist(__DIR__.'/../var/browser/source/failure_'.$name.'__0.html');
        $this->assertCount(2, $html->savedArtifacts()['Saved Source Files']);
    }

    private function restoreVariable(string $name, ?string $previous): void
    {
        if (null === $previous) {
            unset($_SERVER[$name]);

            return;
        }

        $_SERVER[$name] = $previous;
    }

    private function captureDeprecations(callable $callback): array
    {
        $deprecations = [];
        \set_error_handler(static function(int $type, string $message) use (&$deprecations): bool {
            $deprecations[] = $message;

            return true;
        }, \E_USER_DEPRECATED);

        try {
            $callback();
        } finally {
            \restore_error_handler();
        }

        return $deprecations;
    }
}

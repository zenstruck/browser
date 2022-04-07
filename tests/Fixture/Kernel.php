<?php

namespace Zenstruck\Browser\Tests\Fixture;

use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function page1(Request $request): Response
    {
        if ($request->query->has('start-session')) {
            $request->getSession()->set('key', 'value');
        }

        return new Response(\file_get_contents(__DIR__.'/files/page1.html'));
    }

    public function page2(): Response
    {
        return new Response('success');
    }

    public function text(): Response
    {
        return new Response('text content', 200, ['Content-Type' => 'text/plain']);
    }

    public function javascript(): Response
    {
        return new Response(\file_get_contents(__DIR__.'/files/javascript.html'));
    }

    public function xml(): Response
    {
        return new Response(\file_get_contents(__DIR__.'/files/xml.xml'), 200, ['Content-Type' => 'text/xml']);
    }

    public function submitForm(Request $request): Response
    {
        $files = \array_map(
            static function($value) {
                if (\is_array($value)) {
                    return \array_map(fn(UploadedFile $file) => $file->getClientOriginalName(), $value);
                }

                return $value instanceof UploadedFile ? $value->getClientOriginalName() : null;
            },
            $request->files->all()
        );

        if ('e' === $request->request->get('submit_1')) {
            throw new \RuntimeException('fail!');
        }

        if ('r' === $request->request->get('submit_1')) {
            return new RedirectResponse('/redirect1');
        }

        return new JsonResponse(\array_merge(
            $request->request->all(),
            \array_filter($files)
        ));
    }

    public function httpMethod(Request $request): Response
    {
        return new JsonResponse([
            'method' => $request->getMethod(),
            'headers' => $request->headers->all(),
            'query' => $request->query->all(),
            'attributes' => $request->attributes->all(),
            'files' => $request->files->all(),
            'server' => $request->server->all(),
            'request' => $request->request->all(),
            'content' => \json_decode($request->getContent(), true),
            'ajax' => $request->isXmlHttpRequest(),
        ]);
    }

    public function json(Request $request): JsonResponse
    {
        return new JsonResponse(
            $request->getContent(),
            200,
            ['Content-Type' => $request->query->get('content-type', 'application/json')],
            true
        );
    }

    public function exception(): void
    {
        throw new \Exception('exception thrown');
    }

    public function redirect1(): RedirectResponse
    {
        return new RedirectResponse('/redirect2');
    }

    public function redirect2(): RedirectResponse
    {
        return new RedirectResponse('/redirect3');
    }

    public function redirect3(): RedirectResponse
    {
        return new RedirectResponse('/page1');
    }

    public function user(?UserInterface $user = null): Response
    {
        return new Response($user ? "user: {$user->getUserIdentifier()}/{$user->getPassword()}" : 'anon');
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new SecurityBundle();
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        $c->loadFromExtension('framework', [
            'secret' => 'S3CRET',
            'router' => ['utf8' => true],
            'test' => true,
            'profiler' => ['enabled' => true, 'collect' => true],
            'session' => ['storage_factory_id' => 'session.storage.factory.mock_file'],
        ]);
        $c->loadFromExtension('security', [
            'enable_authenticator_manager' => true,
            'password_hashers' => [InMemoryUser::class => 'plaintext'],
            'providers' => ['users' => ['memory' => ['users' => ['kevin' => ['password' => 'pass']]]]],
            'firewalls' => ['main' => []],
        ]);
        $c->register('logger', NullLogger::class); // disable logging
    }

    private function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->add('page1', '/page1')->controller('kernel::page1');
        $routes->add('page2', '/page2')->controller('kernel::page2');
        $routes->add('text', '/text')->controller('kernel::text');
        $routes->add('submit-form', '/submit-form')->controller('kernel::submitForm');
        $routes->add('http-method', '/http-method')->controller('kernel::httpMethod');
        $routes->add('exception', '/exception')->controller('kernel::exception');
        $routes->add('redirect1', '/redirect1')->controller('kernel::redirect1');
        $routes->add('redirect2', '/redirect2')->controller('kernel::redirect2');
        $routes->add('redirect3', '/redirect3')->controller('kernel::redirect3');
        $routes->add('json', '/json')->controller('kernel::json');
        $routes->add('xml', '/xml')->controller('kernel::xml');
        $routes->add('javascript', '/javascript')->controller('kernel::javascript');
        $routes->add('user', '/user')->controller('kernel::user');
    }
}

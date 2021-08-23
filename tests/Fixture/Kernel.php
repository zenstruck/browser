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
use Symfony\Component\Routing\RouteCollectionBuilder;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function page1(): Response
    {
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

    public function submitForm(Request $request): JsonResponse
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
            'content' => $request->getContent(),
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
        if ($user) {
            $username = \method_exists($user, 'getUserIdentifier') ? $user->getUserIdentifier() : $user->getUsername();
        }

        return new Response($user ? "user: {$username}/{$user->getPassword()}" : 'anon');
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
            'session' => ['storage_id' => 'session.storage.mock_file'],
        ]);
        $c->loadFromExtension('security', [
            'encoders' => [User::class => 'plaintext'],
            'providers' => ['users' => ['memory' => ['users' => ['kevin' => ['password' => 'pass']]]]],
            'firewalls' => ['main' => ['anonymous' => true]],
        ]);
        $c->register('logger', NullLogger::class); // disable logging
    }

    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $routes->add('/page1', 'kernel::page1');
        $routes->add('/page2', 'kernel::page2');
        $routes->add('/text', 'kernel::text');
        $routes->add('/submit-form', 'kernel::submitForm');
        $routes->add('/http-method', 'kernel::httpMethod');
        $routes->add('/exception', 'kernel::exception');
        $routes->add('/redirect1', 'kernel::redirect1');
        $routes->add('/redirect2', 'kernel::redirect2');
        $routes->add('/redirect3', 'kernel::redirect3');
        $routes->add('/json', 'kernel::json');
        $routes->add('/xml', 'kernel::xml');
        $routes->add('/javascript', 'kernel::javascript');
        $routes->add('/user', 'kernel::user');
    }
}

<?php

namespace Zenstruck\Browser\Tests\Fixture;

use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\RouteCollectionBuilder;

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

    public function submitForm(Request $request): JsonResponse
    {
        return new JsonResponse(\array_merge(
            $request->request->all(),
            \array_map(fn(UploadedFile $file) => $file->getClientOriginalName(), $request->files->all())
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
            'request' => $request->query->all(),
            'content' => $request->getContent(),
            'ajax' => $request->isXmlHttpRequest(),
        ]);
    }

    public function json(Request $request): JsonResponse
    {
        return new JsonResponse($request->getContent(), 200, [], true);
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

    public function sendEmail(): Response
    {
        $email = (new Email())
            ->from('webmaster@example.com')
            ->to(new Address('kevin@example.com', 'Kevin'))
            ->cc('cc@example.com')
            ->bcc('bcc@example.com')
            ->replyTo('reply@example.com')
            ->attachFromPath(__DIR__.'/files/attachment.txt')
            ->subject('email subject')
            ->html('html body')
            ->text('text body')
        ;

        $email->getHeaders()->addTextHeader('X-PM-Tag', 'reset-password');

        $this->container->get('mailer')->send($email);

        return new Response('success');
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
    }

    public function getLogDir(): string
    {
        return \sys_get_temp_dir().'/zenstruck-browser/logs';
    }

    public function getCacheDir(): string
    {
        return \sys_get_temp_dir().'/zenstruck-browser/cache';
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        $c->loadFromExtension('framework', [
            'secret' => 'S3CRET',
            'router' => ['utf8' => true],
            'test' => true,
            'profiler' => ['enabled' => true, 'collect' => true],
            'mailer' => ['dsn' => 'null://null'],
        ]);
        $c->register('logger', NullLogger::class); // disable logging
        $c->setAlias('mailer', MailerInterface::class)->setPublic(true);
    }

    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $routes->add('/page1', 'kernel::page1');
        $routes->add('/page2', 'kernel::page2');
        $routes->add('/submit-form', 'kernel::submitForm');
        $routes->add('/http-method', 'kernel::httpMethod');
        $routes->add('/exception', 'kernel::exception');
        $routes->add('/redirect1', 'kernel::redirect1');
        $routes->add('/redirect2', 'kernel::redirect2');
        $routes->add('/redirect3', 'kernel::redirect3');
        $routes->add('/send-email', 'kernel::sendEmail');
        $routes->add('/json', 'kernel::json');
    }
}

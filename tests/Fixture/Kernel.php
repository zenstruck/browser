<?php

namespace Zenstruck\Browser\Tests\Fixture;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function page1(): Response
    {
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
        ]);
    }

    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $routes->add('/page1', 'kernel::page1');
    }
}

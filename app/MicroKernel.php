<?php

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;

class MicroKernel extends Kernel
{
    use MicroKernelTrait;

    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        $bundles = [
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new \Symfony\Bundle\TwigBundle\TwigBundle(),
            new \JMS\SerializerBundle\JMSSerializerBundle(),
            new \Playbloom\Satisfy\PlaybloomSatisfyBundle(),
        ];

        return $bundles;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader)
    {
        try {
            $loader->load(__DIR__ . '/config/config_' . $this->environment . '.yml');

            return;
        } catch (FileLocatorFileNotFoundException $e) {
        }

        $loader->load(__DIR__ . '/config/config.yml');
    }

    /**
     * {@inheritdoc}
     */
    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        $controllerBase = 'Playbloom\Satisfy\Controller\\';
        $routes->add('/', 'kernel:indexAction', 'index');
        // security
        $routes->add('/login', 'Playbloom\Satisfy\Controller\SecurityController::loginAction', 'login');
        // repository
        $controller = $controllerBase . 'RepositoryController';
        $routes->add('/admin', $controller . '::indexAction', 'repository')->setMethods(['GET']);
        $routes->add('/admin/new', $controller . '::newAction', 'repository_new')->setMethods(['GET', 'POST']);
        $routes->add('/admin/upload', $controller . '::uploadAction', 'repository_upload')->setMethods(['GET', 'POST']);
        $routes
            ->add('/admin/edit/{repository}', $controller . '::editAction', 'repository_edit')
            ->setMethods(['GET', 'POST'])
            ->setRequirement('repository', '[a-zA-Z0-9_-]+');
        $routes
            ->add('/admin/delete/{repository}', $controller . '::deleteAction', 'repository_delete')
            ->setMethods(['GET', 'DELETE'])
            ->setRequirement('repository', '[a-zA-Z0-9_-]+');
        $routes
            ->add('/admin/configuration', $controllerBase . 'ConfigurationController::indexAction', 'configuration')
            ->setMethods(['GET', 'POST']);
    }

    // optional, to use the standard Symfony cache directory
    public function getCacheDir()
    {
        return __DIR__ . '/../var/cache/' . $this->getEnvironment();
    }

    // optional, to use the standard Symfony logs directory
    public function getLogDir()
    {
        return __DIR__ . '/../var/logs';
    }

    /**
     * @return Response
     */
    public function indexAction()
    {
        $indexFile = __DIR__ . '/../web/index.html';
        if (!file_exists($indexFile)) {
            return $this->getContainer()->get('templating')->renderResponse('unavailable.html.twig');
        }

        return new BinaryFileResponse($indexFile, 200, [], false);
    }
}

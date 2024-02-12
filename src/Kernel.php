<?php

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends Symfony\Component\HttpKernel\Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Playbloom\Satisfy\PlaybloomSatisfyBundle(),
        ];
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $path = __DIR__ . '/../config/config_' . $this->environment . '.yml';
        if (!file_exists($path)) {
            $path = __DIR__ . '/../config/config.yml';
        }

        $container->import($path);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureRoutes(RoutingConfigurator $routes)
    {
        $controllerBase = 'Playbloom\Satisfy\Controller\\';
        $routes
            ->add('index', '/')
            ->controller([$this, 'indexAction']);
        // security
        $routes
            ->add('login', '/login')
            ->controller('Playbloom\Satisfy\Controller\SecurityController::loginAction');
        $routes
            ->add('logout', '/admin/logout');
        // repository
        $controller = $controllerBase . 'RepositoryController';
        $routes
            ->add('repository', '/admin')
            ->controller($controller . '::indexAction')
            ->methods(['GET']);
        $routes
            ->add('repository_', '/admin/')
            ->controller($controller . '::indexAction')
            ->methods(['GET']);
        $routes
            ->add('repository_new', '/admin/new')
            ->controller($controller . '::newAction')
            ->methods(['GET', 'POST']);
        $routes
            ->add('repository_upload', '/admin/upload')
            ->controller($controller . '::uploadAction')
            ->methods(['GET', 'POST']);
        $routes
            ->add('repository_edit', '/admin/edit/{repository}')
            ->controller($controller . '::editAction')
            ->methods(['GET', 'POST'])
            ->requirements(['repository' => '[a-zA-Z0-9_-]+']);
        $routes
            ->add('repository_delete', '/admin/delete/{repository}')
            ->controller($controller . '::deleteAction')
            ->methods(['GET', 'DELETE'])
            ->requirements(['repository' => '[a-zA-Z0-9_-]+']);
        $routes
            ->add('configuration', '/admin/configuration')
            ->controller($controllerBase . 'ConfigurationController::indexAction')
            ->methods(['GET', 'POST']);
        // satis interaction
        $routes
            ->add('satis_build', '/admin/satis/build')
            ->controller($controllerBase . 'SatisController::buildAction')
            ->methods(['GET']);
        $routes
            ->add('satis_build_run', '/admin/satis/buildRun')
            ->controller($controllerBase . 'SatisController::buildRunAction')
            ->methods(['GET']);
        // webhooks
        $routes
            ->add('webhook_bitbucket', '/webhook/bitbucket')
            ->controller($controllerBase . 'WebhookController::bitbucketAction')
            ->methods(['GET', 'POST']);
        $routes
            ->add('webhook_github', '/webhook/github')
            ->controller($controllerBase . 'WebhookController::githubAction')
            ->methods(['GET', 'POST']);
        $routes
            ->add('webhook_gitea', '/webhook/gitea')
            ->controller($controllerBase . 'WebhookController::giteaAction')
            ->methods(['GET', 'POST']);
        $routes
            ->add('webhook_gitlab', '/webhook/gitlab')
            ->controller($controllerBase . 'WebhookController::gitlabAction')
            ->methods(['GET', 'POST']);
        $routes
            ->add('webhook_devops', '/webhook/devops')
            ->controller($controllerBase . 'WebhookController::devopsAction')
            ->methods(['GET', 'POST']);
    }

    // optional, to use the standard Symfony cache directory
    public function getCacheDir(): string
    {
        return __DIR__ . '/../var/cache/' . $this->getEnvironment();
    }

    // optional, to use the standard Symfony logs directory
    public function getLogDir(): string
    {
        return __DIR__ . '/../var/log';
    }

    public function indexAction(): Response
    {
        $indexFile = __DIR__ . '/../public/index.html';
        if (!file_exists($indexFile)) {
            return new Response($this->getContainer()->get('twig')->render('unavailable.html.twig'));
        }

        return new Response(file_get_contents($indexFile));
    }
}

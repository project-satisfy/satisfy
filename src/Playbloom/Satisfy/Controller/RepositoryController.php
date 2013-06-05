<?php

namespace Playbloom\Satisfy\Controller;

use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Playbloom\Satisfy\Model\Repository;
use Playbloom\Satisfy\Form\Type\RepositoryType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Repository controller provider.
 *
 * @author Ludovic Fleury <ludo.fleury@gmail.com>
 */
class RepositoryController implements ControllerProviderInterface
{
    /**
     * Connect
     *
     * @param  Application $app
     *
     * @return ControllerCollection
     */
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $repositoryProvider = function ($repository) use ($app) {
            return $app['satis']->findOneRepository($repository);
        };

        /**
         * repository
         *
         * GET /
         * List all repositories definitions
         */
        $controllers->get('/', function () use ($app) {
            $repositories = $app['satis']->findAllRepositories();

            return $app['twig']->render('home.html.twig', array('repositories' => $repositories));
        })
        ->bind('repository');

        /**
         * repository_new
         *
         * GET /new
         * Get the form to add a new repository definition
         */
        $controllers->get('/new', function () use ($app) {
            $repository = (new Repository())
                ->setType($app['composer.repository.type_default'])
                ->setUrl($app['composer.repository.url_default']);

            $form = $app['form.factory']->create(new RepositoryType(), $repository);

            return $app['twig']->render('new.html.twig', array('form' => $form->createView()));
        })
        ->bind('repository_new');

        /**
         * repository_create
         *
         * POST /
         * Add a new repository definition
         */
        $controllers->post('/', function (Request $request) use ($app) {
            $form = $app['form.factory']->create(new RepositoryType(), new Repository());

            $form->bind($request);

            if ($form->isValid()) {
                $app['satis']->add($form->getData());

                return $app->redirect($app['url_generator']->generate('repository'));
            }

            return $app['twig']->render('new.html.twig', array('form' => $form->createView()));
        })
        ->bind('repository_create');

        /**
         * repository_edit
         *
         * GET /edit
         * Get the form to edit an existing repository definition
         */
        $controllers->get('/edit/{repository}', function (Repository $repository) use ($app) {
            $form = $app['form.factory']->create(new RepositoryType(), $repository);

            return $app['twig']->render('edit.html.twig', array('form' => $form->createView()));
        })
        ->bind('repository_edit')
        ->assert('repository', '[a-zA-Z0-9_-]+')
        ->convert('repository', $repositoryProvider);

        /**
         * repository_update
         *
         * PUT /
         * Update an existing repository definition
         */
        $controllers->put('/{repository}', function (Repository $repository, Request $request) use ($app) {
            $form = $app['form.factory']->create(new RepositoryType(), new Repository());

            $form->bind($request);

            if ($form->isValid()) {
                $app['satis']->update($repository, $form->getData()->getUrl());

                return $app->redirect($app['url_generator']->generate('repository'));
            }

            return $app['twig']->render('edit.html.twig', array('form' => $form->createView()));
        })
        ->bind('repository_update')
        ->assert('repository', '[a-zA-Z0-9_-]+')
        ->convert('repository', $repositoryProvider);

        /**
         * repository_erase
         *
         * GET /delete/{repository}
         * Get the form to delete a repository definition
         */
        $controllers->get('/delete/{repository}', function (Repository $repository) use ($app) {
            $form = $app['form.factory']->create();

            return $app['twig']->render('delete.html.twig', array('form' => $form->createView(), 'repository' => $repository));
        })
        ->bind('repository_erase')
        ->assert('repository', '[a-zA-Z0-9_-]+')
        ->convert('repository', $repositoryProvider);

        /**
         * repository_delete
         *
         * DELETE /{repository}
         * Delete a repository definition
         */
        $controllers->delete('/{repository}', function (Repository $repository, Request $request) use ($app) {
            $form = $app['form.factory']->create();

            $form->bind($request);

            if ($form->isValid()) {
                $app['satis']->delete($repository);

                return $app->redirect($app['url_generator']->generate('repository'));
            }

            return $app['twig']->render('delete.html.twig', array('form' => $form->createView(), 'repository' => $repository));
        })
        ->bind('repository_delete')
        ->assert('repository', '[a-zA-Z0-9_-]+')
        ->convert('repository', $repositoryProvider);

        return $controllers;
    }
}

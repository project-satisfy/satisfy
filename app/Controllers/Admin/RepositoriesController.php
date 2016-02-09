<?php

namespace Playbloom\Satisfy\Controllers\Admin;

use Playbloom\Satisfy\Controllers\AbstractController;
use Playbloom\Satisfy\Forms\Types\RepositoryType;
use Playbloom\Satisfy\Models\Repository;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class RepositoriesController extends AbstractController
{
    public function create()
    {
        $repository = (new Repository())
            ->setType($this->app['config']['composer']['repository']['default_type'])
            ->setUrl($this->app['config']['composer']['repository']['default_url']);

        $form = $this->app['form.factory']->create(
            new RepositoryType(),
            $repository,
            ['pattern' => implode('|', $this->app['config']['composer']['repository']['patterns'])]
        );

        return $this->app['twig']->render(
            'new.html.twig',
            ['form' => $form->createView()]
        );
    }

    public function store(Request $request)
    {
        $form = $this->app['form.factory']->create(
            new RepositoryType(),
            new Repository(),
            ['pattern' => implode('|', $this->app['config']['composer']['repository']['patterns'])]
        );

        $form->bind($request);

        if ($form->isValid()) {
            $this->app['satis']->add($form->getData());

            return $this->app->redirect(
                $this->app['url_generator']->generate('admin_index')
            );
        }

        return $this->app['twig']->render(
            'new.html.twig',
            ['form' => $form->createView()]
        );
    }

    public function edit($repositoryId)
    {
        $form = $this->app['form.factory']->create(
            new RepositoryType(),
            $this->app['satis']->findOneRepository($repositoryId),
            ['pattern' => implode('|', $this->app['config']['composer']['repository']['patterns'])]
        );

        return $this->app['twig']->render(
            'edit.html.twig',
            ['form' => $form->createView()]
        );
    }

    public function update($repositoryId, Request $request)
    {
        $repository = $this->app['satis']->findOneRepository($repositoryId);

        $form = $this->app['form.factory']->create(
            new RepositoryType(),
            new Repository(),
            ['pattern' => implode('|', $this->app['config']['composer']['repository']['patterns'])]
        );

        $form->bind($request);

        if ($form->isValid()) {
            $this->app['satis']->update(
                $repository,
                $form->getData()->getUrl()
            );

            return $this->app->redirect(
                $this->app['url_generator']->generate('admin_index')
            );
        }

        return $this->app['twig']->render(
            'edit.html.twig',
            ['form' => $form->createView()]
        );
    }

    public function remove($repositoryId)
    {
        $repository = $this->app['satis']->findOneRepository($repositoryId);

        $form = $this->app['form.factory']->create();

        return $this->app['twig']->render(
            'delete.html.twig',
            ['form' => $form->createView(), 'repository' => $repository]
        );
    }

    public function destroy($repositoryId, Request $request)
    {
        $repository = $this->app['satis']->findOneRepository($repositoryId);

        $form = $this->app['form.factory']->create();

        $form->bind($request);

        if ($form->isValid()) {
            $this->app['satis']->delete($repository);

            return $this->app->redirect(
                $this->app['url_generator']->generate('admin_index')
            );
        }

        return $this->app['twig']->render(
            'delete.html.twig',
            ['form' => $form->createView(), 'repository' => $repository]
        );
    }
}

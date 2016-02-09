<?php

namespace Playbloom\Satisfy\Controllers\Admin;

use Playbloom\Satisfy\Controllers\AbstractController;

class AdminController extends AbstractController
{
    public function index()
    {
        $repositories = $this->app['satis']->findAllRepositories();

        $context = $this->app['request_context'];
        $config = array(
            'repositories' => array(
                array(
                    'type' => 'composer',
                    'url'  => $context->getScheme() . '://' . $context->getHost(),
                ),
            ),
        );

        if (! empty($this->app['config']['composer']['repository']['options'])) {
            $config['repositories'][0]['options'] = $this->app['config']['composer']['repository']['options'];
        }

        return $this->app['twig']->render(
            'home.html.twig',
            compact('config', 'repositories')
        );
    }
}

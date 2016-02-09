<?php

namespace Playbloom\Satisfy\Controllers;

class IndexController extends AbstractController
{
    public function index()
    {
        $indexPath = web_path('index.html');

        if (! file_exists($indexPath)) {
            return $this->app['twig']
                ->render('unavailable.html.twig');
        }

        return $this->app
            ->sendFile($indexPath);
    }
}

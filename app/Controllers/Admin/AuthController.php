<?php

namespace Playbloom\Satisfy\Controllers\Admin;

use Playbloom\Satisfy\Controllers\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class AuthController extends AbstractController
{
    public function login(Request $request)
    {
        return $this->app['twig']->render(
            'login.html.twig',
            [
                'error'         => $this->app['security.last_error']($request),
                'last_username' => $this->app['session']->get('_security.last_username'),
            ]
        );
    }
}

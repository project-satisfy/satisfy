<?php

namespace Playbloom\Satisfy\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller to handle user login
 *
 * @author Damien Pitard <damien.pitard@gmail.com>
 */
class SecurityController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->get('/login', function(Request $request) use ($app) {

            return $app['twig']->render('login.html.twig', array(
                'error'         => $app['security.last_error']($request),
                'last_username' => $app['session']->get('_security.last_username'),
            ));
        })->bind('login');;

        return $controllers;
    }
}
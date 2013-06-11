<?php

namespace Playbloom\Satisfy\Provider;

use Silex\ServiceProviderInterface;
use Silex\Application;

use Symfony\Component\HttpFoundation\Response;

/**
 * Open id security service provider class
 *
 * This class is strongly inspired by KnpLabs/marketplace
 * @see https://github.com/KnpLabs/marketplace
 *
 * @author Ludovic Fleury <ludo.fleury@gmail.com>
 */
class SecurityServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app->before(function() use ($app) {
            $app['session']->start();

            if ($app['request']->get('_route') == 'logout') {
                return;
            }

            if (!$app['session']->has('username')) {
                $openid = new \LightOpenID($_SERVER['SERVER_NAME']);

                if (!$openid->mode) {
                    $openid->identity = 'https://www.google.com/accounts/o8/id';
                    $openid->required = array(
                        'email' => 'contact/email',
                        'firstname' => 'namePerson/first',
                        'lastname' => 'namePerson/last',
                    );

                    return $app->redirect($openid->authUrl());
                } else {
                    if ($openid->validate()) {
                        $attributes = $openid->getAttributes();

                        $app['session']->set('username', $attributes['contact/email']);
                        $app['session']->set('fullname', $attributes['namePerson/first'].' '.$attributes['namePerson/last']);
                    }
                }
            }

            $app['twig']->addGlobal('username', $app['session']->get('username'));
            $app['twig']->addGlobal('fullname', $app['session']->get('fullname'));

            if (isset($app['auth']) && !$app['auth']($app['session']->get('username'))) {
                $app['session']->remove('username');
                $app['session']->remove('fullname');

                return new Response($app['twig']->render('forbidden.html.twig'), 403);
            }
        });
    }

    public function boot(Application $app)
    {

    }
}

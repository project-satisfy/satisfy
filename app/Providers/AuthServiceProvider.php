<?php

namespace Playbloom\Satisfy\Providers;

use Silex\Application;
use Silex\Provider\SecurityServiceProvider;
use Silex\ServiceProviderInterface;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;

class AuthServiceProvider implements ServiceProviderInterface
{
    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param  \Silex\Application  $app
     */
    public function register(Application $app)
    {
        if ($app['config']['auth']['use_login_form']) {
            $users = [];

            $app['auth'] = $app->share(function () {
                return function ($username) {
                    return (bool) preg_match('/@your-organization\.tld$/', $username);
                };
            });

            foreach ($app['config']['auth']['users'] as $username => $password) {
                $users[$username] = array('ROLE_ADMIN', $password);
            }

            $app['security.firewalls'] = [
                'login' => [
                    'pattern' => '^/admin/login$',
                    'anonymous' => true
                ],
                'secured' => [
                    'pattern' => '^/admin.*$',
                    'form' => [
                        'login_path' => '/admin/login',
                        'check_path' => '/admin/login_check'
                    ],
                    'logout' => [
                        'logout_path' => '/admin/logout',
                        'target_url' => '/admin/'
                    ],
                    'users' => $users
                ],
            ];

            $app->register(new SecurityServiceProvider());

            $app['security.encoder.digest'] = $app->share(function () {
                return new MessageDigestPasswordEncoder('sha1', false, 0);
            });
        }
    }

    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registered
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     *
     * @param  \Silex\Application  $app
     */
    public function boot(Application $app)
    {
        // ...
    }
}

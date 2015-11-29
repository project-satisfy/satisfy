<?php

/**
 * Satis configuration file
 */
$app['satis.filename'] = __DIR__.'/../satis.json';

/**
 * Satis file formating options
 *
 * See http://php.net/manual/en/json.constants.php for constants description
 */
$app['satis.file_formatting'] = JSON_PRETTY_PRINT;

/**
 * Satis auditlog (cheap backup/versioning) path
 */
$app['satis.auditlog'] = __DIR__.'/data';

/**
 * Satis main configuration class
 */
$app['satis.class'] = 'Playbloom\\Satisfy\\Model\\Configuration';

/**
 * Default values for a new repository
 */
$app['composer.repository.type_default'] = 'git';
$app['composer.repository.url_default'] = 'git@github.com:YourAccount/';

/**
 * Composer repository options for ssl or auth
 */
$app['composer.repository.options'] = [];

/**
 * Default repository url pattern
 */
$app['repository.pattern'] = 'https://github.com/[a-zA-Z0-9-_]+/[a-zA-Z0-9-_\.]+(.git)?|git@github.com:[a-zA-Z0-9-_]+/[a-zA-Z0-9-_\.]+.git';

/**
 * More restrictive username/email constraints for production
 */
$app['auth'] = $app->share(function() {
    return function($username) {
        return (bool) preg_match('/@your-organization\.tld$/', $username);
    };
});

/**
 * If the simple standard login form should be used to restrict admin section
 */
$app['auth.use_login_form'] = false;

/**
 * Users authorized to access admin section (an array of username => password)
 * Default credentials are: admin / foo.
 *
 * You can generate a new password with the following command:
 *
 *      php -r "echo hash('sha1', 'mypassword');"
 *
 */
$app['auth.users'] = array(
    'admin' => '0beec7b5ea3f0fdbc95d0dd47f3c5bc275da8a33',
    'john' => 'd6b4e84ee7f31d88617a6b60421451272ebf1a3a',
);

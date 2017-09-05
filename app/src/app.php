<?php

use Silex\Application;
use Silex\Provider\AssetServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\HttpFragmentServiceProvider;
use Silex\Provider\LocaleServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\SecurityServiceProvider;

$app = new Application();

$app->register(new ServiceControllerServiceProvider());
$app->register(new AssetServiceProvider());
$app->register(new TwigServiceProvider());
$app->register(new HttpFragmentServiceProvider());
$app['twig'] = $app->extend('twig', function ($twig, $app) {
    // add custom globals, filters, tags, ...

    return $twig;
});

$app->register(new LocaleServiceProvider());
$app->register(
    new TranslationServiceProvider(),
    [
        'locale' => 'pl',
        'locale_fallbacks' => array('en'),
    ]
);
$app->extend('translator', function ($translator, $app) {
    $translator->addResource('xliff', __DIR__.'/../translations/messages.en.xlf', 'en', 'messages');
    $translator->addResource('xliff', __DIR__.'/../translations/validators.en.xlf', 'en', 'validators');

    $translator->addResource('xliff', __DIR__.'/../translations/messages.pl.xlf', 'pl', 'messages');
    $translator->addResource('xliff', __DIR__.'/../translations/validators.pl.xlf', 'pl', 'validators');

    return $translator;
});

$app->register(
    new DoctrineServiceProvider(),
    array(
        'db.options' => array(
            'driver'    => 'pdo_mysql',
            'host'      => 'localhost',
            'dbname'    => 'mydb',
            'user'      => 'mciszczon',
            'password'  => '',
            'charset'   => 'utf8',
            'driverOptions' => array(
                1002 => 'SET NAMES utf8',
            ),
        ),
    )
);

$app->register(new FormServiceProvider());
$app->register(new ValidatorServiceProvider());

$app->register(new SessionServiceProvider());

$app->register(
    new SecurityServiceProvider(),
    [
        'security.firewalls' => [
            'dev' => [
                'pattern' => '^/(_(profiler|wdt)|css|images|js)/',
                'security' => false,
            ],
            'main' => [
                'pattern' => '^.*$',
                'form' => [
                    'login_path' => 'auth_login',
                    'check_path' => 'auth_login_check',
                    'default_target_path' => 'homepage',
                    'username_parameter' => 'login_type[login]',
                    'password_parameter' => 'login_type[password]',
                ],
                'anonymous' => true,
                'logout' => [
                    'logout_path' => 'auth_logout',
                    'target_url' => 'homepage',
                ],
                'users' => function () use ($app) {
                    return new Provider\UserProvider($app['db']);
                },
            ],
        ],
        'security.access_rules' => [
            ['^/auth.+$', 'IS_AUTHENTICATED_ANONYMOUSLY'],
            ['^/(.+edit.*)|(.+delete.*)|(.+add.*)$', 'ROLE_ADMIN'],
            ['^/user/.+$', 'ROLE_USER'],
            ['^/project/.+$', 'ROLE_USER'],
            ['^/.+$', 'ROLE_ADMIN'],
        ],
        'security.role_hierarchy' => [
            'ROLE_ADMIN' => ['ROLE_USER'],
        ],
    ]
);

return $app;

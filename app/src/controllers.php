<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Controller\HelloController;
use Controller\BookmarkController;
use Controller\TagController;
use Controller\AuthController;
use Controller\UserController;
use Controller\ProjectController;
use Controller\TaskController;

//Request::setTrustedProxies(array('127.0.0.1'));

$app->get('/', function () use ($app) {
    return $app['twig']->render('index.html.twig', array());
})
    ->bind('homepage')
;

$app->mount('/hello', new HelloController());
$app->mount('/bookmark', new BookmarkController());
$app->mount('/tag', new TagController());
$app->mount('/user', new UserController());
$app->mount('/project', new ProjectController());
$app->mount('/auth', new AuthController());
$app->mount('/project/{project_id}/task', new TaskController());

$app->error(function (\Exception $e, Request $request, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    // 404.html, or 40x.html, or 4xx.html, or error.html
    $templates = array(
        'errors/'.$code.'.html.twig',
        'errors/'.substr($code, 0, 2).'x.html.twig',
        'errors/'.substr($code, 0, 1).'xx.html.twig',
        'errors/default.html.twig',
    );

    return new Response($app['twig']->resolveTemplate($templates)->render(array('code' => $code)), $code);
});
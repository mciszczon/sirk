<?php
/**
 * Controllers configuration
 */
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Controller\IndexController;
use Controller\AuthController;
use Controller\UserController;
use Controller\ProjectController;
use Controller\TaskController;
use Controller\MessageController;
use Controller\NoteController;
use Controller\FileController;

//Request::setTrustedProxies(array('127.0.0.1'));

$app->mount('/', new IndexController());
$app->mount('/user', new UserController());
$app->mount('/project', new ProjectController());
$app->mount('/auth', new AuthController());
$app->mount('/project/{project_id}/task', new TaskController());
$app->mount('/project/{project_id}/message', new MessageController());
$app->mount('/project/{project_id}/note', new NoteController());
$app->mount('/project/{project_id}/file', new FileController());

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
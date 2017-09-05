<?php
/**
 * Bookmark controller.
 *
 * @copyright (c) 2016 Tomasz Chojna
 * @link http://epi.chojna.info.pl
 */
namespace Controller;

use Repository\ProjectRepository;
use Repository\UserRepository;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Form\ProjectType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Class HelloController.
 *
 * @package Controller
 */
class ProjectController implements ControllerProviderInterface
{
    /**
     * Routing settings.
     *
     * @param \Silex\Application $app Silex application
     *
     * @return \Silex\ControllerCollection Result
     */
    public function connect(Application $app)
    {
        $controller = $app['controllers_factory'];
        $controller->get('/', [$this, 'indexAction'])->bind('project_index');
        $controller->get('/page/{page}', [$this, 'indexAction'])
            ->value('page', 1)
            ->bind('project_index_paginated');
        $controller->get('/{id}', [$this, 'viewAction'])
            ->assert('id', '[1-9]\d*')
            ->bind('project_view');
        $controller->match('/{id}/delete', [$this, 'deleteAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('project_delete');
        $controller->match('/add', [$this, 'addAction'])
            ->method('POST|GET')
            ->bind('project_add');
        $controller->match('/{id}/edit', [$this, 'editAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('project_edit');

        return $controller;
    }

    /**
     * Index action.
     *
     * @param \Silex\Application $app Silex application
     *
     * @return string Response
     */
    public function indexAction(Application $app, $page = 1)
    {
        $projectRepository = new ProjectRepository($app['db']);

        return $app['twig']->render(
            'project/index.html.twig',
            ['paginator' => $projectRepository->findAllPaginated($page)]
        );
    }

    /**
     *
     *
     * @param Application $app
     * @param int $id
     * @return mixed
     */
    public function viewAction(Application $app, $id)
    {
        $projectRepository = new ProjectRepository($app['db']);

        return $app['twig']->render(
            'project/view.html.twig',
            [
                'project' => $projectRepository->findOneById($id),
            ]
        );
    }

    /**
     * Add action.
     *
     * @param \Silex\Application                        $app     Silex application
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */

    /**
     * Edit action.
     *
     * @param \Silex\Application                        $app     Silex application
     * @param int                                       $id      Record id
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */

    public function editAction(Application $app, $id, Request $request)
    {
        $projectRepository = new ProjectRepository($app['db']);
        $project = $projectRepository->findOneById($id);

        if (!$project) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('project_index'));
        }

        $form = $app['form.factory']->createBuilder(
            ProjectType::class,
            $project,
            [
                'user_repository' => new UserRepository($app['db']),
            ]
        )->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $projectRepository->save($form->getData());

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_edited',
                ]
            );

            //return $app->redirect($app['url_generator']->generate('project_index'), 301);
        }

        return $app['twig']->render(
            'project/edit.html.twig',
            [
                'project' => $project,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Delete action.
     *
     * @param \Silex\Application                        $app     Silex application
     * @param int                                       $id      Record id
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function deleteAction(Application $app, $id, Request $request)
    {
        $projectRepository = new ProjectRepository($app['db']);
        $project = $projectRepository->findOneById($id);

        if (!$project) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('project_index'));
        }

        $form = $app['form.factory']->createBuilder(FormType::class, $project)->add('id', HiddenType::class)->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_deleted',
                ]
            );

            return $app->redirect(
                $app['url_generator']->generate('project_index'),
                301
            );
        }

        return $app['twig']->render(
            'project/delete.html.twig',
            [
                'project' => $project,
                'form' => $form->createView(),
            ]
        );
    }
}
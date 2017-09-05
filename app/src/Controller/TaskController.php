<?php
/**
 * Bookmark controller.
 *
 * @copyright (c) 2016 Tomasz Chojna
 * @link http://epi.chojna.info.pl
 */
namespace Controller;

use Repository\PriorityRepository;
use Repository\ProjectRepository;
use Repository\TaskRepository;
use Repository\UserRepository;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Form\TaskType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Security\Core\User\User;

/**
 * Class HelloController.
 *
 * @package Controller
 */
class TaskController implements ControllerProviderInterface
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
        $controller->get('/', [$this, 'indexAction'])->bind('task_index');
        $controller->get('/page/{page}', [$this, 'indexAction'])
            ->value('page', 1)
            ->bind('task_index_paginated');
        $controller->get('/{id}', [$this, 'viewAction'])
            ->assert('id', '[1-9]\d*')
            ->bind('task_view');
        $controller->match('/{id}/delete', [$this, 'deleteAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('task_delete');
        $controller->match('/add', [$this, 'addAction'])
            ->method('POST|GET')
            ->bind('task_add');
        $controller->match('/{id}/edit', [$this, 'editAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('task_edit');

        return $controller;
    }

    /**
     * Index action.
     *
     * @param \Silex\Application $app Silex application
     *
     * @return string Response
     */
    public function indexAction(Application $app, $page = 1, $project_id)
    {
        $projectRepository = new ProjectRepository($app['db']);
        $taskRepository = new TaskRepository($app['db']);

        return $app['twig']->render(
            'task/index.html.twig',
            [
                'project' => $projectRepository->findOneById($project_id),
                'tasks' => $taskRepository->findLinkedTasks($project_id),
                'paginator' => $taskRepository->findAllPaginated($page)
            ]
        );
    }

    /**
     *
     *
     * @param Application $app
     * @param int $id
     * @return mixed
     */
    public function viewAction(Application $app, $id, $project_id)
    {
        $projectRepository = new ProjectRepository($app['db']);
        $taskRepository = new TaskRepository($app['db']);

        return $app['twig']->render(
            'task/view.html.twig',
            [
                'project' => $projectRepository->findOneById($project_id),
                'task' => $taskRepository->findOneById($id),
                'user' => $taskRepository->findLinkedUser($id)
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
    public function addAction(Application $app, Request $request, $project_id)
    {
        $projectRepository = new ProjectRepository($app['db']);

        $task = [];

        $form = $app['form.factory']->createBuilder(
            TaskType::class,
            $task,
            [
                'task_repository' => new TaskRepository($app['db']),
                'project_repository' => new ProjectRepository($app['db']),
                'priorities_repository' => new PriorityRepository($app['db']),
                'user_repository' => new UserRepository($app['db']),
                'project_id' => $project_id,
            ]
        )->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $taskRepository = new TaskRepository($app['db']);
            $taskRepository->save($form->getData());

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_added',
                ]
            );

            return $app->redirect($app['url_generator']->generate('task_index', [ 'project_id' => $project_id ]), 301);
        }

        return $app['twig']->render(
            'task/add.html.twig',
            [
                'task' => $task,
                'form' => $form->createView(),
                'project' => $projectRepository->findOneById($project_id),
            ]
        );
    }

    /**
     * Edit action.
     *
     * @param \Silex\Application                        $app     Silex application
     * @param int                                       $id      Record id
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */

    /**
     * Delete action.
     *
     * @param \Silex\Application                        $app     Silex application
     * @param int                                       $id      Record id
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function deleteAction(Application $app, $id, $project_id, Request $request)
    {
        $taskRepository = new TaskRepository($app['db']);
        $projectRepository = new ProjectRepository($app['db']);

        $task = $taskRepository->findOneById($id);
        $user = $taskRepository->findLinkedUser($id);
        $project = $projectRepository->findOneById($project_id);

        if (!$task) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('task_index', [ 'project_id' => $project_id ]));
        }

        $form = $app['form.factory']->createBuilder(FormType::class, $task)->add('id', HiddenType::class)->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $taskRepository->delete($form->getData());

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_deleted',
                ]
            );

//            return $app->redirect(
//                $app['url_generator']->generate('task_index', [ 'project_id' => $project_id ]),
//                301
//            );
        }

        return $app['twig']->render(
            'task/delete.html.twig',
            [
                'task' => $task,
                'project' => $project,
                'user' => $user,
                'form' => $form->createView(),
            ]
        );
    }
}
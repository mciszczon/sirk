<?php
/**
 * Message controller.
 */
namespace Controller;

use Repository\ProjectRepository;
use Repository\MessageRepository;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Form\MessageType;

/**
 * Class MessageController
 * @package Controller
 */
class MessageController extends BaseController
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
        $controller->get('/', [$this, 'indexAction'])->bind('message_index');
        $controller->get('/page/{page}', [$this, 'indexAction'])
            ->value('page', 1)
            ->bind('message_index_paginated');
        $controller->match('/{id}/delete', [$this, 'deleteAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('message_delete');
        $controller->match('/add', [$this, 'addAction'])
            ->method('POST|GET')
            ->bind('message_add');
        $controller->match('/{id}/edit', [$this, 'editAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('message_edit');

        return $controller;
    }

    /**
     * Index action.
     *
     * @param \Silex\Application $app Silex application
     * @param int $page Page number
     * @param int $project_id Project ID
     *
     * @return string Response
     */
    public function indexAction(Application $app, $page = 1, $project_id)
    {
        $projectRepository = new ProjectRepository($app['db']);
        $messageRepository = new MessageRepository($app['db']);

        $currentUserId = $this->getUserId($app);
        if (!$projectRepository->checkIfUserHasProject($currentUserId, $project_id)) {
            if (!$this->checkIfAdmin($app, $currentUserId)) {
                return $app->redirect($app['url_generator']->generate('project_index'));
            }
        }

        return $app['twig']->render(
            'message/index.html.twig',
            [
                'project' => $projectRepository->findOneById($project_id),
                'paginator' => $messageRepository->findAllPaginated($page, $project_id),
                'user_id' => $currentUserId,
            ]
        );
    }

    /**
     * Add action.
     *
     * @param \Silex\Application                        $app     Silex application
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     * @param int $project_id Project ID
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function addAction(Application $app, Request $request, $project_id)
    {
        $projectRepository = new ProjectRepository($app['db']);

        $currentUserId = $this->getUserId($app);
        if (!$projectRepository->checkIfUserHasProject($currentUserId, $project_id)) {
            if (!$this->checkIfAdmin($app, $currentUserId)) {
                return $app->redirect($app['url_generator']->generate('project_index'));
            }
        }

        $message = [];

        $form = $app['form.factory']->createBuilder(
            MessageType::class,
            $message,
            [
                'project_id' => $project_id,
                'user_id' => $this->getUserId($app),
            ]
        )->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $messageRepository = new MessageRepository($app['db']);
            $messageRepository->save($form->getData());

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_added',
                ]
            );

            return $app->redirect($app['url_generator']->generate('message_index', [ 'project_id' => $project_id ]), 301);
        }

        return $app['twig']->render(
            'message/add.html.twig',
            [
                'message' => $message,
                'form' => $form->createView(),
                'project' => $projectRepository->findOneById($project_id),
            ]
        );
    }

    /**
     * Edit action.
     *
     * @param \Silex\Application                        $app        Silex application
     * @param \Symfony\Component\HttpFoundation\Request $request    HTTP Request
     * @param int                                       $id         Record id
     * @param int                                       $project_id Project ID
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function editAction(Application $app, Request $request, $id, $project_id)
    {
        $projectRepository = new ProjectRepository($app['db']);

        $currentUserId = $this->getUserId($app);
        if (!$projectRepository->checkIfUserHasProject($currentUserId, $project_id)) {
            if (!$this->checkIfAdmin($app, $currentUserId)) {
                return $app->redirect($app['url_generator']->generate('project_index'));
            }
        }

        $messageRepository = new MessageRepository($app['db']);
        $message = $messageRepository->findOneById($id);

        if(!$messageRepository->checkIfUserHasMessage($currentUserId, $id)) {
            if (!$this->checkIfAdmin($app, $currentUserId)) {
                return $app->redirect($app['url_generator']->generate('message_index', [ 'project_id' => $project_id ]));
            }
        }

        if (!$message) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('message_index', [ 'project_id' => $project_id ]));
        }

        $form = $app['form.factory']->createBuilder(
            MessageType::class,
            $message,
            [
                'project_id' => $project_id,
                'user_id' => $this->getUserId($app),
            ]
        )->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $messageRepository = new MessageRepository($app['db']);
            $messageRepository->save($form->getData());

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_edited',
                ]
            );

            return $app->redirect($app['url_generator']->generate('message_index', [ 'project_id' => $project_id ]), 301);
        }

        return $app['twig']->render(
            'message/edit.html.twig',
            [
                'message' => $message,
                'form' => $form->createView(),
                'project' => $projectRepository->findOneById($project_id),
            ]
        );
    }

    /**
     * Delete action.
     *
     * @param \Silex\Application                        $app        Silex application
     * @param int                                       $id         Record id
     * @param int                                       $project_id Project ID
     * @param \Symfony\Component\HttpFoundation\Request $request    HTTP Request
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function deleteAction(Application $app, $id, $project_id, Request $request)
    {
        $messageRepository = new MessageRepository($app['db']);
        $projectRepository = new ProjectRepository($app['db']);

        $currentUserId = $this->getUserId($app);
        if (!$projectRepository->checkIfUserHasProject($currentUserId, $project_id)) {
            if (!$this->checkIfAdmin($app, $currentUserId)) {
                return $app->redirect($app['url_generator']->generate('project_index'));
            }
        }

        $message = $messageRepository->findOneById($id);
        $project = $projectRepository->findOneById($project_id);

        if(!$messageRepository->checkIfUserHasMessage($currentUserId, $id)) {
            if (!$this->checkIfAdmin($app, $currentUserId)) {
                return $app->redirect($app['url_generator']->generate('message_index', [ 'project_id' => $project_id ]));
            }
        }

        if (!$message) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('message_index', [ 'project_id' => $project_id ]));
        }

        $form = $app['form.factory']->createBuilder(FormType::class, $message)->add('id', HiddenType::class)->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $messageRepository = new MessageRepository($app['db']);
            $messageRepository->delete($form->getData());

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_deleted',
                ]
            );

            return $app->redirect(
                $app['url_generator']->generate('message_index', [ 'project_id' => $project_id ]),
                301
            );
        }

        return $app['twig']->render(
            'message/delete.html.twig',
            [
                'message' => $message,
                'project' => $project,
                'form' => $form->createView(),
            ]
        );
    }
}
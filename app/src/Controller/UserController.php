<?php
/**
 * User controller.
 */
namespace Controller;

use Repository\UserRepository;
use Silex\Application;
use Form\UserType;
use Form\UserEditType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Class UserController.
 *
 * @package Controller
 */
class UserController extends BaseController
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
        $controller->get('/', [$this, 'indexAction'])
            ->bind('user_index');
        $controller->get('/page/{page}', [$this, 'indexAction'])
            ->value('page', 1)
            ->bind('user_index_paginated');
        $controller->get('/{id}', [$this, 'viewAction'])
            ->assert('id', '[1-9]\d*')
            ->bind('user_view');
        $controller->match('/add', [$this, 'addAction'])
            ->method('POST|GET')
            ->bind('user_add');
        $controller->match('/{id}/delete', [$this, 'deleteAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('user_delete');
        $controller->match('/{id}/edit', [$this, 'editAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('user_edit');
        $controller->get('/profile', [$this, 'profileAction'])
            ->assert('id', '[1-9]\d*')
            ->bind('profile_view');

        return $controller;
    }

    /**
     * Index action.
     *
     * @param \Silex\Application $app  Silex application
     * @param int                $page Current page number
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function indexAction(Application $app, $page = 1)
    {
        $currentUserId = $this->getUserId($app);
        if (!$this->checkIfAdmin($app, $currentUserId)) {
            return $app->redirect($app['url_generator']->generate('profile_view'));
        }

        $userRepository = new UserRepository($app['db']);

        if ($this->checkIfAdmin($app, $currentUserId)) {
            return $app['twig']->render(
                'user/index.html.twig',
                [
                    'paginator' => $userRepository->findAllPaginated($page),
                    'user' => $currentUserId,
                ]
            );
        } else {
            return $app->redirect($app['url_generator']->generate('profile_view'));
        }

    }

    /**
     * View action.
     *
     * @param \Silex\Application $app Silex application
     * @param int                $id  User ID
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function viewAction(Application $app, $id)
    {
        $userRepository = new UserRepository($app['db']);

        return $app['twig']->render(
            'user/view.html.twig',
            [
                'user' => $userRepository->getUserById($id),
                'current_user' => $this->getUserId($app),
            ]
        );
    }

    /**
     * Profile action.
     *
     * @param \Silex\Application $app Silex application
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function profileAction(Application $app)
    {
        $userRepository = new UserRepository($app['db']);
        $userId = $this->getUserId($app);
        $user = $userRepository->findOneById($userId);

        return $app['twig']->render(
            'user/view.html.twig',
            [
                'user' => $user,
                'current_user' => true,
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
    public function addAction(Application $app, Request $request)
    {
        $currentUserId = $this->getUserId($app);
        if (!$this->checkIfAdmin($app, $currentUserId)) {
            return $app->redirect($app['url_generator']->generate('profile_view'));
        }

        $user = [];

        $form = $app['form.factory']->createBuilder(
            UserType::class,
            $user,
            [
                'user_repository' => new userRepository($app['db']),
            ]
        )->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $tagRepository = new UserRepository($app['db']);
            $tagRepository->save($app, $data);

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_added',
                ]
            );

            return $app->redirect($app['url_generator']->generate('homepage'), 301);
        }

        return $app['twig']->render(
            'user/add.html.twig',
            [
                'user' => $user,
                'form' => $form->createView(),
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
    public function editAction(Application $app, $id, Request $request)
    {
        $currentUserId = $this->getUserId($app);
        if (!$this->checkIfAdmin($app, $currentUserId)) {
            return $app->redirect($app['url_generator']->generate('profile_view'));
        }

        $userRepository = new UserRepository($app['db']);
        $user = $userRepository->getUserById($id);

        if (!$user) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('user_index'));
        }

        $form = $app['form.factory']->createBuilder(
            UserEditType::class,
            $user,
            [
                'user_repository' => new UserRepository($app['db']),
                'user_id' => $id,
            ]
        )->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userRepository->save($app, $form->getData());

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_edited',
                ]
            );

            return $app->redirect($app['url_generator']->generate('user_index'), 301);
        }

        return $app['twig']->render(
            'user/edit.html.twig',
            [
                'user' => $user,
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
        $currentUserId = $this->getUserId($app);
        if (!$this->checkIfAdmin($app, $currentUserId)) {
            return $app->redirect($app['url_generator']->generate('profile_view'));
        }

        if ($currentUserId === $id ) {
            return $app->redirect($app['url_generator']->generate('user_view', ['id' => $id]));
        }

        $userRepository = new UserRepository($app['db']);
        $user = $userRepository->getUserById($id);

        if (!$user) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('user_index'));
        }

        $form = $app['form.factory']->createBuilder(FormType::class, $user)->add('id', HiddenType::class)->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userRepository->delete($form->getData());

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_deleted',
                ]
            );

            return $app->redirect(
                $app['url_generator']->generate('user_index'),
                301
            );
        }

        return $app['twig']->render(
            'user/delete.html.twig',
            [
                'user' => $user,
                'form' => $form->createView(),
            ]
        );
    }
}
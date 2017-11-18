<?php
/**
 * Note controller.
 */
namespace Controller;

use Repository\ProjectRepository;
use Repository\NoteRepository;
use Silex\Application;
use Form\NoteType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Class NoteController
 * @package Controller
 */
class NoteController extends BaseController
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
        $controller->get('/', [$this, 'indexAction'])->bind('note_index');
        $controller->get('/page/{page}', [$this, 'indexAction'])
            ->value('page', 1)
            ->bind('note_index_paginated');
        $controller->match('/{id}', [$this, 'viewAction'])
            ->assert('id', '[1-9]\d*')
            ->bind('note_view');
        $controller->match('/{id}/delete', [$this, 'deleteAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('note_delete');
        $controller->match('/add', [$this, 'addAction'])
            ->method('POST|GET')
            ->bind('note_add');
        $controller->match('/{id}/edit', [$this, 'editAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('note_edit');

        return $controller;
    }

    /**
     * Index action.
     *
     * @param \Silex\Application $app Silex application
     * @param int $page Page numver
     * @param int $project_id Project ID
     *
     * @return string Response
     */
    public function indexAction(Application $app, $page = 1, $project_id)
    {
        $projectRepository = new ProjectRepository($app['db']);
        $noteRepository = new NoteRepository($app['db']);

        $currentUserId = $this->getUserId($app);
        if (!$projectRepository->checkIfUserHasProject($currentUserId, $project_id)) {
            if (!$this->checkIfAdmin($app, $currentUserId)) {
                return $app->redirect($app['url_generator']->generate('project_index'));
            }
        }

        return $app['twig']->render(
            'note/index.html.twig',
            [
                'project' => $projectRepository->findOneById($project_id),
                'paginator' => $noteRepository->findAllPaginatedForUserAndProject($page, $currentUserId, $project_id),
            ]
        );
    }

    /**
     * View action.
     *
     * @param Application $app
     * @param int $id Record ID
     * @param int $project_id Project ID
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function viewAction(Application $app, $id, $project_id)
    {
        $projectRepository = new ProjectRepository($app['db']);
        $noteRepository = new NoteRepository($app['db']);

        $currentUserId = $this->getUserId($app);
        if (!$projectRepository->checkIfUserHasProject($currentUserId, $project_id)) {
            if (!$this->checkIfAdmin($app, $currentUserId)) {
                return $app->redirect($app['url_generator']->generate('project_index'));
            }
        }

        return $app['twig']->render(
            'note/view.html.twig',
            [
                'project' => $projectRepository->findOneById($project_id),
                'note' => $noteRepository->findOneById($id),
            ]
        );
    }

    /**
     * Add action.
     *
     * @param \Silex\Application                        $app        Silex application
     * @param \Symfony\Component\HttpFoundation\Request $request    HTTP Request
     * @param int                                       $project_id Project ID
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

        $note = [];

        $form = $app['form.factory']->createBuilder(
            NoteType::class,
            $note,
            [
                'project_id' => $project_id,
                'user_id' => $this->getUserId($app),
            ]
        )->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $noteRepository = new NoteRepository($app['db']);
            $noteRepository->save($form->getData());

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_added',
                ]
            );

            return $app->redirect($app['url_generator']->generate('note_index', [ 'project_id' => $project_id ]), 301);
        }

        return $app['twig']->render(
            'note/add.html.twig',
            [
                'note' => $note,
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
        $noteRepository = new NoteRepository($app['db']);
        $projectRepository = new ProjectRepository($app['db']);
        $note = $noteRepository->findOneById($id);

        $currentUserId = $this->getUserId($app);
        if (!$projectRepository->checkIfUserHasProject($currentUserId, $project_id)) {
            if (!$this->checkIfAdmin($app, $currentUserId)) {
                return $app->redirect($app['url_generator']->generate('project_index'));
            }
        }

        if (!$note) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('note_index', [ 'project_id' => $project_id ]));
        }

        $form = $app['form.factory']->createBuilder(
            NoteType::class,
            $note,
            [
                'project_id' => $project_id,
                'user_id' => $this->getUserId($app),
            ]
        )->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $noteRepository = new NoteRepository($app['db']);
            $noteRepository->save($form->getData());

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_edited',
                ]
            );

            return $app->redirect($app['url_generator']->generate('note_view', [ 'id' => $id, 'project_id' => $project_id ]), 301);
        }

        return $app['twig']->render(
            'note/edit.html.twig',
            [
                'note' => $note,
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
        $noteRepository = new NoteRepository($app['db']);
        $projectRepository = new ProjectRepository($app['db']);

        $note = $noteRepository->findOneById($id);
        $project = $projectRepository->findOneById($project_id);

        $currentUserId = $this->getUserId($app);
        if (!$projectRepository->checkIfUserHasProject($currentUserId, $project_id)) {
            if (!$this->checkIfAdmin($app, $currentUserId)) {
                return $app->redirect($app['url_generator']->generate('project_index'));
            }
        }

        if (!$note) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('note_index', [ 'project_id' => $project_id ]));
        }

        $form = $app['form.factory']->createBuilder(FormType::class, $note)->add('id', HiddenType::class)->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $noteRepository = new NoteRepository($app['db']);
            $noteRepository->delete($form->getData());

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_deleted',
                ]
            );

            return $app->redirect(
                $app['url_generator']->generate('note_index', [ 'project_id' => $project_id ]),
                301
            );
        }

        return $app['twig']->render(
            'note/delete.html.twig',
            [
                'note' => $note,
                'project' => $project,
                'form' => $form->createView(),
            ]
        );
    }

}
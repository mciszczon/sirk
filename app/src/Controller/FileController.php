<?php
/**
 * File controller.
 */
namespace Controller;

use Form\AttachmentType;
use Repository\ProjectRepository;
use Repository\FileRepository;
use Service\FileUploader;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Class FileController
 * @package Controller
 */
class FileController extends BaseController
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
        $controller->get('/', [$this, 'indexAction'])->bind('file_index');
        $controller->get('/page/{page}', [$this, 'indexAction'])
            ->value('page', 1)
            ->bind('file_index_paginated');
        $controller->get('/{id}', [$this, 'viewAction'])
            ->assert('id', '[1-9]\d*')
            ->bind('file_view');
        $controller->match('/{id}/delete', [$this, 'deleteAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('file_delete');
        $controller->match('/add', [$this, 'addAction'])
            ->method('POST|GET')
            ->bind('file_add');

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
        $fileRepository = new FileRepository($app['db']);

        $currentUserId = $this->getUserId($app);
        if (!$projectRepository->checkIfUserHasProject($currentUserId, $project_id)) {
            if (!$this->checkIfAdmin($app, $currentUserId)) {
                return $app->redirect($app['url_generator']->generate('project_index'));
            }
        }

        return $app['twig']->render(
            'file/index.html.twig',
            [
                'project' => $projectRepository->findOneById($project_id),
                'paginator' => $fileRepository->findAllPaginated($page, $project_id),
                'user_id' => $currentUserId,
            ]
        );
    }

    /**
     * View action.
     *
     * @param \Silex\Application $app Silex application
     * @param int $id File ID
     * @param int $project_id Project ID
     *
     * @return string Response
     */
    public function viewAction(Application $app, $id, $project_id)
    {
        $projectRepository = new ProjectRepository($app['db']);
        $currentUserId = $this->getUserId($app);
        if (!$projectRepository->checkIfUserHasProject($currentUserId, $project_id)) {
            if (!$this->checkIfAdmin($app, $currentUserId)) {
                return $app->redirect($app['url_generator']->generate('project_index'));
            }
        }

        $fileRepository = new FileRepository($app['db']);

        return $app['twig']->render(
            'file/view.html.twig',
            [
                'project' => $projectRepository->findOneById($project_id),
                'file' => $fileRepository->findOneById($id),
                'current_user' => $currentUserId,
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

        $file = [];

        $form = $app['form.factory']->createBuilder(
            AttachmentType::class,
            $file,
            [
                'project_id' => $project_id,
                'user_id' => $this->getUserId($app),
            ]
        )->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file  = $form->getData();
            $fileUploader = new FileUploader($app['config.files_directory']);
            $fileName = $fileUploader->upload($file['file']);
            $file['file'] = $fileName;
            $fileRepository = new FileRepository($app['db']);
            $fileRepository->save($file);

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type'    => 'success',
                    'message' => 'message.element_successfully_added',
                ]
            );

            return $app->redirect(
                $app['url_generator']->generate('file_index', [ 'project_id' => $project_id ]),
                301
            );
        }

        return $app['twig']->render(
            'file/add.html.twig',
            [
                'project' => $projectRepository->findOneById($project_id),
                'file'  => $file,
                'form' => $form->createView(),
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
        $projectRepository = new ProjectRepository($app['db']);
        $fileRepository = new FileRepository($app['db']);

        $currentUserId = $this->getUserId($app);
        if (!$projectRepository->checkIfUserHasProject($currentUserId, $project_id)) {
            if (!$fileRepository->checkIfUserHasFile($currentUserId, $project_id)) {
                if (!$this->checkIfAdmin($app, $currentUserId)) {
                    return $app->redirect($app['url_generator']->generate('project_index'));
                }
            }
        }

        $file = $fileRepository->findOneById($id);
        $project = $projectRepository->findOneById($project_id);

        if(!$fileRepository->checkIfUserHasFile($currentUserId, $id)) {
            if (!$this->checkIfAdmin($app, $currentUserId)) {
                return $app->redirect($app['url_generator']->generate('file_view', [ 'id' => $id, 'project_id' => $project_id ]));
            }
        }

        if (!$file) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('file_index', [ 'project_id' => $project_id ]));
        }

        $form = $app['form.factory']->createBuilder(FormType::class, $file)->add('id', HiddenType::class)->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fileRepository = new FileRepository($app['db']);
            $fileRepository->delete($form->getData());

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_deleted',
                ]
            );

            return $app->redirect(
                $app['url_generator']->generate('file_index', [ 'project_id' => $project_id ]),
                301
            );
        }

        return $app['twig']->render(
            'file/delete.html.twig',
            [
                'file' => $file,
                'project' => $project,
                'form' => $form->createView(),
            ]
        );
    }

}
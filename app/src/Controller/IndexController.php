<?php
/**
 * Index controller.
 */
namespace Controller;

use Silex\Application;

/**
 * Class IndexController
 * @package Controller
 */
class IndexController extends BaseController
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
        $controller->get('/', [$this, 'indexAction'])->bind('homepage');

        return $controller;
    }

    /**
     * Index action.
     *
     * @param \Silex\Application $app Silex application
     *
     * @return string Response
     */
    public function indexAction(Application $app)
    {
        $currentUserId = $this->getUserId($app);

        if ($currentUserId === '') {
            return $app['twig']->render('index.html.twig');
        }
        else {
            return $app->redirect($app['url_generator']->generate('project_index'));
        }
    }
}
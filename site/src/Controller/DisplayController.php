<?php

namespace Neukom\Component\NeukomTemplating\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView;

class DisplayController extends BaseController {

    public function display($cachable = false, $urlparams = array()) {
        $document = Factory::getDocument();
        $viewName = $this->input->getCmd('view', 'login');
        $viewFormat = $document->getType();

        $view = $this->getHtmlView($viewName, $viewFormat);
        $view->setModel($this->getModel('Message'), true);

        $view->document = $document;
        $view->display();
    }

    private function getHtmlView($viewName, $viewFormat): HtmlView {
        return $this->getView($viewName, $viewFormat);
    }
}

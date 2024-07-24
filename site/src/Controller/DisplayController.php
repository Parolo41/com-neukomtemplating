<?php

namespace Neukom\Component\NeukomTemplating\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView;

class DisplayController extends BaseController {

	public function __construct($config = [], MVCFactoryInterface $factory = null, $app = null, $input = null) {
		parent::__construct($config, $factory, $app, $input);
	}

    public function display($cachable = false, $urlparams = array()) {
        $document = Factory::getDocument();
        $viewName = $this->input->getCmd('view', 'login');
        $viewFormat = $document->getType();

        $view = $this->getHtmlView($viewName, $viewFormat);
        $view->setModel($this->getModel('Template'), true);

        $view->document = $document;
        $view->display();
    }

    private function getHtmlView($viewName, $viewFormat): HtmlView {
        return $this->getView($viewName, $viewFormat);
    }
}

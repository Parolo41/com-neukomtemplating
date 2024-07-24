<?php

namespace Neukom\Component\NeukomTemplating\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\FormController;

class TemplateController extends FormController {
    protected $view_item = 'form';
    
    public function getModel($name = 'form', $prefix = '', $config = ['ignore_request' => true])
	{
        Factory::getApplication()->enqueueMessage('getModel');
        Factory::getApplication()->enqueueMessage('getModel 2');
		return parent::getModel($name, $prefix, ['ignore_request' => false]);
        Factory::getApplication()->enqueueMessage('getModel end');
	}

    protected function allowEdit($data = [], $key = 'id') {
        return true;
	}

    protected function getRedirectToItemAppend($recordId = 0, $urlVar = 'id'){
		// Need to override the parent method completely.
		$tmpl = $this->input->get('tmpl');

		$append = '';

		// Setup redirect info.

		if ($tmpl) {
			$append .= '&tmpl=' . $tmpl;
		}

		$append .= '&layout=edit';

		$append .= '&' . $urlVar . '=' . (int) $recordId;

        /*
		$itemId = $this->input->getInt('Itemid');
		$return = $this->getReturnPage();
		$catId  = $this->input->getInt('catid');


		if ($itemId) {
			$append .= '&Itemid=' . $itemId;
		}

		if ($catId) {
			$append .= '&catid=' . $catId;
		}

		if ($return) {
			$append .= '&return=' . base64_encode($return);
		}
        */

		return $append;
	}
}

?>
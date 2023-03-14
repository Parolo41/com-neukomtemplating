<?php

namespace Neukom\Component\NeukomTemplating\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;

class TemplateController extends FormController {
    protected function allowEdit($data = [], $key = 'id') {
        return true;
	}
}

?>
<?php

namespace Neukom\Component\NeukomTemplating\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Neukom\Component\NeukomTemplating\Administrator\Table\TemplateTable;

class TemplateModel extends AdminModel {

    public $typeAlias = 'com_neukomtemplating.template';

    public function getForm($data = array(), $loadData = true) {
        $form = $this->loadForm($this->typeAlias, 'template', array('control' => 'jform', 'load_data' => $loadData));

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    protected function loadFormData() {
        $app = Factory::getApplication();

        $data = $this->getItem();

        $this->preprocessData($this->typeAlias, $data);

        return $data;
    }

    protected function prepareTable($table) {
        if ($table instanceof TemplateTable) {
            $table->generateAlias();
        }
    }
}

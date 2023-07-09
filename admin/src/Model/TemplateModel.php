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
        $form = Factory::getApplication()->input->getVar('jform', array(),'post', 'array');

        $table->show_detail_page = (isset($form['show_detail_page']) ? 1 : 0);
        $table->allow_create = (isset($form['allow_create']) ? 1 : 0);
        $table->allow_edit = (isset($form['allow_edit']) ? 1 : 0);

        if ($table instanceof TemplateTable) {
            $table->generateAlias();
        }
    }
}

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
        $form = Factory::getApplication()->input->getVar('jform', array(), 'post', 'array');

        $fieldInputs = [
            'name',
            'showInForm',
            'label',
            'type',
            'selectOptions',
            'required',
        ];

        $fields = array();

        foreach ($fieldInputs as $input) {
            $values = Factory::getApplication()->input->getVar('field_' . $input, array(), 'post', 'array');

            for ($i = 0; $i < count($values); $i++) {
                $fields[$i][$input] = $values[$i];
            }
        }

        $parameterInputs = [
            'name',
            'default',
            'insertIntoDb',
        ];

        $urlParameters = array();

        foreach ($parameterInputs as $input) {
            $values = Factory::getApplication()->input->getVar('parameter_' . $input, array(), 'post', 'array');

            for ($i = 0; $i < count($values); $i++) {
                $urlParameters[$i][$input] = $values[$i];
            }
        }

        $joinedInputs = [
            'name',
            'displayField',
            'connectionType',
            'NToOne-foreignKey',
            'NToOne-remoteId',
            'OneToN-foreignKey',
            'NToN-intermediateTable',
            'NToN-intermediateLocalKey',
            'NToN-intermediateRemoteKey',
            'NToN-remoteId',
            'foreignFields',
            'alias',
            'showInForm',
            'formName',
        ];

        $joinedTables = array();

        foreach ($joinedInputs as $input) {
            $values = Factory::getApplication()->input->getVar('joined_' . $input, array(), 'post', 'array');

            for ($i = 0; $i < count($values); $i++) {
                $joinedTables[$i][$input] = $values[$i];
            }
        }

        $table->fields = json_encode($fields);
        $table->url_parameters = json_encode($urlParameters);
        $table->joined_tables = json_encode($joinedTables);

        $table->show_detail_page = (isset($form['show_detail_page']) ? 1 : 0);
        $table->enable_search = (isset($form['enable_search']) ? 1 : 0);
        $table->enable_pagination = (isset($form['enable_pagination']) ? 1 : 0);
        $table->allow_create = (isset($form['allow_create']) ? 1 : 0);
        $table->allow_edit = (isset($form['allow_edit']) ? 1 : 0);

        if ($table instanceof TemplateTable) {
            $table->generateAlias();
        }
    }
}

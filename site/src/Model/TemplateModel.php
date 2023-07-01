<?php

namespace Neukom\Component\NeukomTemplating\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\CMS\Uri\Uri;

class TemplateModel extends ItemModel {

    /**
     * Returns a message for display
     * @param integer $pk Primary key of the "message item", currently unused
     * @return object Message object
     */
    public function getItem($pk = null): object {
        $input = Factory::getApplication()->getInput();
        $templateConfigName = $input->getString('templateConfigName');

        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->select(
            $db->quoteName(['id', 'header', 'template', 'footer', 'tablename', 'fields', 'condition', 'allow_edit'])
        );
        $query->from($db->quoteName('#__neukomtemplating_templates'));
        $query->where('name = "' . $templateConfigName . '"');
        $db->setQuery($query);
        $templateConfig = $db->loadObject();

        error_log(print_r($templateConfig, true));
		error_log("uri: " . Uri::getInstance()->toString());

        $idFieldName = 'id';

        $fields = [];
        $fieldNames = [$idFieldName];

        foreach (explode(';', str_replace(' ', '', $templateConfig->fields)) as $field) {
            $fieldConfigArray = explode(':', $field);

            $fieldName = $fieldConfigArray[0];
            $fieldType = isset($fieldConfigArray[1]) ? $fieldConfigArray[1] : "text";
            $fieldRequired = isset($fieldConfigArray[2]) ? $fieldConfigArray[2] == "1" : false;
            $showFieldInForm = isset($fieldConfigArray[3]) ? $fieldConfigArray[3] == "1" : true;

            if ($showFieldInForm) {
                $fields[] = [$fieldName, $fieldType, $fieldRequired, $showFieldInForm];
            }
            
            $fieldNames[] = $fieldName;
        }

        $dataQuery = $db->getQuery(true);
        $dataQuery->select($db->quoteName($fieldNames));
        $dataQuery->from($db->quoteName('#__' . $templateConfig->tablename));
        $dataQuery->where($templateConfig->condition);
        $db->setQuery($dataQuery);
        $data = $db->loadObjectList();

        $item = new \stdClass();
        $item->id = $templateConfig->id;
        $item->templateName = $templateConfigName;
        $item->tableName = $templateConfig->tablename;
        $item->header = $templateConfig->header;
        $item->template = $templateConfig->template;
        $item->footer = $templateConfig->footer;
        $item->allowEdit = $templateConfig->allow_edit;
        $item->data = $data;
        
        $item->fields = $fields;

        return $item;
    }
}

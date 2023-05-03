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

        foreach (explode(',', $templateConfig->fields) as $field) {
            $nameTypeArray = explode(':', $field);

            $fieldName = $nameTypeArray[0];
            $fieldType = (isset($nameTypeArray[1])) ? $nameTypeArray[1] : "text";

            $fields[] = [$fieldName, $fieldType];
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
        $item->header = $templateConfig->header;
        $item->template = $templateConfig->template;
        $item->footer = $templateConfig->footer;
        $item->allowEdit = $templateConfig->allow_edit;
        $item->data = $data;
        
        $item->fields = $fields;

        return $item;
    }
}

<?php

namespace Neukom\Component\NeukomTemplating\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ItemModel;

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
            $db->quoteName(['id', 'header', 'template', 'footer', 'tablename', 'fields', 'condition'])
        );
        $query->from($db->quoteName('#__neukomtemplating_templates'));
        $query->where('name = "' . $templateConfigName . '"');
        $db->setQuery($query);
        $templateConfig = $db->loadObject();

        error_log(print_r($templateConfig, true));

        $idFieldName = 'id';

        $dataQuery = $db->getQuery(true);
        $dataQuery->select(
            $db->quoteName(array_merge(array($idFieldName), explode(',', $templateConfig->fields)))
        );
        $dataQuery->from($db->quoteName('#__' . $templateConfig->tablename));
        $dataQuery->where($templateConfig->condition);
        $db->setQuery($dataQuery);
        $data = $db->loadObjectList();

        $item = new \stdClass();
        $item->id = $templateConfig->id;
        $item->templateName = $templateConfigName;
        $item->fields = $templateConfig->fields;
        $item->header = $templateConfig->header;
        $item->template = $templateConfig->template;
        $item->footer = $templateConfig->footer;
        $item->data = $data;
        return $item;
    }
}

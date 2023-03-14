<?php

namespace Neukom\Component\NeukomTemplating\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ItemModel;

class FormModel extends Neukom\Component\NeukomTemplating\Site\Model\TemplateModel {
    public function save($data){
        $templateName = $data['templateName'];
        $recordId = $data['recordId'];

        $db = $this->getDbo();
        $query = $db->getQuery(true);

        $fieldUpdates = array();

        foreach (explode(',', $data['fields']) as $field) {
            $fieldUpdates[] = $db->quoteName($field) . ' = ' . $db->quote($data[$field]);
        }

        $query->update($db->quoteName('#__' . $templateName));
        $query->set($fieldUpdates);
        $query->where($db->quoteName('id') . ' = ' . $recordId);
        $db->setQuery($query);

        $result = $db->execute();

        return $result;
    }
}

?>
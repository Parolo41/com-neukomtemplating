<?php

namespace Neukom\Component\NeukomTemplating\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ItemModel;

class MessageModel extends ItemModel {

    /**
     * Returns a message for display
     * @param integer $pk Primary key of the "message item", currently unused
     * @return object Message object
     */
    public function getItem($pk = null): object {
        $input = Factory::getApplication()->getInput();
        $testField = $input->getString('testField');

        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->select(
            $db->quoteName(['template'])
        );
        $query->from($db->quoteName('#__neukomtemplating_templates'));
        $query->where('name = "'.$testField.'"');
        $db->setQuery($query);
        $template = $db->loadObject();

        $item = new \stdClass();
        $item->message = 'This is my message, and this is my message: ' . $testField;
        $item->template = $template->template;
        return $item;
    }
}

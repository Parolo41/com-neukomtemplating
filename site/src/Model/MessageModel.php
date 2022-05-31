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

        $item = new \stdClass();
        $item->message = 'This is my message, and this is my message: ' . $testField;
        return $item;
    }
}

<?php

namespace Neukom\Component\NeukomTemplating\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;

class TemplatesModel extends ListModel {
    public function __construct($config = []) {
        parent::__construct($config);
    }

    protected function getListQuery() {
        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true);
        $query->select(
            $db->quoteName(['id', 'name', 'template'])
        );
        $query->from($db->quoteName('#__neukomtemplating_templates'));
        return $query;
    }
}

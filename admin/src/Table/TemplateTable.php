<?php

namespace Neukom\Component\NeukomTemplating\Administrator\Table;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

class TemplateTable extends Table {
    public function __construct(DatabaseDriver $db) {
        $this->typeAlias = 'com_neukomtemplating.template';
        parent::__construct('#__neukomtemplating_templates', 'id', $db);
    }

    public function generateAlias() {
        if (empty($this->alias)) {
            $this->alias = $this->name;
        }
        $this->alias = ApplicationHelper::stringURLSafe($this->alias, $this->language);
        if (trim(str_replace('-', '', $this->alias)) == '') {
            $this->alias = Factory::getDate()->format('Y-m-d-H-i-s');
        }
        return $this->alias;
    }
}

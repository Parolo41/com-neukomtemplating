<?php

namespace Neukom\Component\NeukomTemplating\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ItemModel;

class FormModel extends TemplateModel {
    public $typeAlias = 'com_neukomtemplating.template';
    protected $formName = 'form';

    public function getForm($data = [], $loadData = true) {
        Factory::getApplication()->enqueueMessage('getForm');

		$form = parent::getForm($data, $loadData);

        /*
		if ($id = $this->getState('foo.id') && Associations::isEnabled()) {
			$associations = Associations::getAssociations('com_foos', '#__foos_details', 'com_foos.item', $id);

			// Make fields read only
			if (!empty($associations)) {
				$form->setFieldAttribute('language', 'readonly', 'true');
				$form->setFieldAttribute('language', 'filter', 'unset');
			}
		}
        */

		return $form;
	}

    public function getItem($itemId = null): object {
		$itemId = (int) (!empty($itemId)) ? $itemId : $this->getState('template.id');

		$table = $this->getTable();

		try {
			if (!$table->load($itemId)) {
				return false;
			}
		} catch (Exception $e) {
			Factory::getApplication()->enqueueMessage($e->getMessage());
			return false;
		}

		$properties = $table->getProperties();
		$value      = ArrayHelper::toObject($properties, 'JObject');

		$value->params = new Registry($value->params);

		return $value;
	}
    
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

	protected function preprocessForm(Form $form, $data, $group = 'foo') {
		return parent::preprocessForm($form, $data, $group);
	}

    public function getTable($name = 'Template', $prefix = 'Administrator', $options = []) {
		return parent::getTable($name, $prefix, $options);
	}
}

?>
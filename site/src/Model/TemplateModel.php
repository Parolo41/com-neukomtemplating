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
            $db->quoteName(['id', 'header', 'template', 'footer', 'tablename', 'fields', 'condition', 'allow_edit', 'joined_tables'])
        );
        $query->from($db->quoteName('#__neukomtemplating_templates'));
        $query->where('name = "' . $templateConfigName . '"');
        $db->setQuery($query);
        $templateConfig = $db->loadObject();

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

        if ($templateConfig->joined_tables != "") {
            $joinedTables = $this->parseJoinedTables($templateConfig->joined_tables);
        }

        foreach ($data as $record) {
            $this->queryJoinedTables($record, $joinedTables);
        }

        error_log(var_export($data, true));

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
        $item->joinedTables = $joinedTables;

        return $item;
    }

    private function parseJoinedTables($joinedTablesString) {
        $joinedTables = [];

        foreach (explode(';', str_replace(' ', '', $joinedTablesString)) as $joinedTable) {
            $joinedTableConfigArray = explode(':', $joinedTable);

            $joinedTableObject = new \stdClass();

            $joinedTableObject->name = $joinedTableConfigArray[0];
            $joinedTableObject->displayField = $joinedTableConfigArray[1];
            $joinedTableObject->connectionType = $joinedTableConfigArray[2];
            $joinedTableObject->connectionInfo = explode(',', $joinedTableConfigArray[3]);
            $joinedTableObject->fields = explode(',', $joinedTableConfigArray[4]);
            $joinedTableObject->showInForm = $joinedTableConfigArray[5] == "1";
            $joinedTableObject->options = $this->queryJoinedTableOptions($joinedTableObject);

            $joinedTables[] = $joinedTableObject;
        }

        return $joinedTables;
    }

    private function queryJoinedTables($record, $joinedTables) {
        $db = $this->getDbo();

        foreach ($joinedTables as $joinedTable) {
            if ($joinedTable->connectionType == "NToOne") {

                $joinedTableQuery = $db->getQuery(true);

                $foreignKeyName = $joinedTable->connectionInfo[0];
                $idFieldName = $joinedTable->connectionInfo[1];
                
                if ($record->{$foreignKeyName} == "") {
                    $record->{$joinedTable->name} = [];
                    continue;
                }

                $joinedTableQuery->select($db->quoteName($joinedTable->fields));
                $joinedTableQuery->from($db->quoteName('#__' . $joinedTable->name));
                $joinedTableQuery->where($db->quoteName($idFieldName) . ' = ' . $record->{$foreignKeyName});

                $db->setQuery($joinedTableQuery);
                $data = $db->loadObjectList();
    
                $record->{$joinedTable->name} = $data;
            }
        }
    }

    private function queryJoinedTableOptions($joinedTable) {
        $db = $this->getDbo();

        if ($joinedTable->showInForm == false) {
            return [];
        }

        if ($joinedTable->connectionType == "NToOne") {
            $joinedTableOptionsQuery = $db->getQuery(true);

            $idFieldName = $joinedTable->connectionInfo[1];

            $joinedTableOptionsQuery->select($db->quoteName([$idFieldName, $joinedTable->displayField]));
            $joinedTableOptionsQuery->from($db->quoteName('#__' . $joinedTable->name));

            $db->setQuery($joinedTableOptionsQuery);
            $data = $db->loadObjectList();

            return $data;
        }
    }
}

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
            $db->quoteName(['id', 'header', 'template', 'footer', 'tablename', 'id_field_name', 'fields', 'condition', 'sorting', 'allow_edit', 'allow_create', 'joined_tables'])
        );
        $query->from($db->quoteName('#__neukomtemplating_templates'));
        $query->where('name = "' . $templateConfigName . '"');
        $db->setQuery($query);
        $templateConfig = $db->loadObject();

        $fields = [];
        $fieldNames = [$templateConfig->id_field_name];

        foreach (explode(';', str_replace(' ', '', $templateConfig->fields)) as $field) {
            $fieldConfigArray = explode(':', $field);

            $fieldName = $fieldConfigArray[0];

            if ($fieldName == $templateConfig->id_field_name) {
                continue;
            }

            $fieldType = isset($fieldConfigArray[1]) ? $fieldConfigArray[1] : "text";
            $fieldRequired = isset($fieldConfigArray[2]) ? $fieldConfigArray[2] == "1" : false;
            $showFieldInForm = isset($fieldConfigArray[3]) ? $fieldConfigArray[3] == "1" : true;
            $displayName = isset($fieldConfigArray[4]) ? $fieldConfigArray[4] : $fieldName;

            if ($showFieldInForm) {
                $fields[] = [$fieldName, $fieldType, $fieldRequired, $showFieldInForm, $displayName];
            }
            
            $fieldNames[] = $fieldName;
        }

        $dataQuery = $db->getQuery(true);
        $dataQuery->select($db->quoteName($fieldNames));
        $dataQuery->from($db->quoteName('#__' . $templateConfig->tablename));

        if (trim($templateConfig->condition) != "") {
            $dataQuery->where($templateConfig->condition);
        }

        if (trim($templateConfig->sorting) != "") {
            $dataQuery->order($templateConfig->sorting);
        }

        $db->setQuery($dataQuery);
        $data = $db->loadObjectList();

        $joinedTables = [];

        if ($templateConfig->joined_tables != "") {
            $joinedTables = $this->parseJoinedTables($templateConfig->joined_tables);

            foreach ($data as $record) {
                $this->queryJoinedTables($record, $joinedTables, $templateConfig->id_field_name);
            }
        }

        $item = new \stdClass();
        $item->id = $templateConfig->id;
        $item->templateName = $templateConfigName;
        $item->tableName = $templateConfig->tablename;
        $item->idFieldName = $templateConfig->id_field_name;
        $item->header = $templateConfig->header;
        $item->template = $templateConfig->template;
        $item->footer = $templateConfig->footer;
        $item->allowEdit = ($templateConfig->allow_edit == "1");
        $item->allowCreate = ($templateConfig->allow_create == "1");
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

    private function queryJoinedTables($record, $joinedTables, $idFieldName) {
        $db = $this->getDbo();

        foreach ($joinedTables as $joinedTable) {
            if ($joinedTable->connectionType == "NToOne") {
                $joinedTableQuery = $db->getQuery(true);

                $foreignKeyName = $joinedTable->connectionInfo[0];
                $joinedIdFieldName = $joinedTable->connectionInfo[1];
                
                if ($record->{$foreignKeyName} == "") {
                    $record->{$joinedTable->name} = [];
                    continue;
                }

                $selectedFields = $joinedTable->fields;

                if (!in_array($joinedIdFieldName, $selectedFields)) {
                    $selectedFields[] = $joinedIdFieldName;
                }

                $joinedTableQuery->select($db->quoteName($selectedFields));
                $joinedTableQuery->from($db->quoteName('#__' . $joinedTable->name));
                $joinedTableQuery->where($db->quoteName($joinedIdFieldName) . ' = ' . $record->{$foreignKeyName});

                $db->setQuery($joinedTableQuery);
                $data = $db->loadObjectList();
    
                $record->{$joinedTable->name} = $data;
            } else if ($joinedTable->connectionType == "OneToN") {
                $joinedTableQuery = $db->getQuery(true);

                $foreignKeyName = $joinedTable->connectionInfo[0];

                $selectedFields = $joinedTable->fields;

                $joinedTableQuery->select($db->quoteName($selectedFields));
                $joinedTableQuery->from($db->quoteName('#__' . $joinedTable->name));
                $joinedTableQuery->where($db->quoteName($foreignKeyName) . ' = ' . $record->{$idFieldName});

                $db->setQuery($joinedTableQuery);
                $data = $db->loadObjectList();
    
                $record->{$joinedTable->name} = $data;
            } else if ($joinedTable->connectionType == "NToN") {
                $joinedTableQuery = $db->getQuery(true);

                $intermediateTableName = $joinedTable->connectionInfo[0];
                $localForeignKeyField = 'interm.' . $joinedTable->connectionInfo[1];
                $remoteForeignKeyField = 'interm.' . $joinedTable->connectionInfo[2];
                $remoteIdField = 'remote.' . $joinedTable->connectionInfo[3];

                $selectedFields = [$localForeignKeyField, $remoteForeignKeyField];

                foreach ($joinedTable->fields as $field) {
                    $selectedFields[] = 'remote.' . $field;
                }

                if (!in_array($remoteIdField, $selectedFields)) {
                    $selectedFields[] = $remoteIdField;
                }

                $joinedTableQuery->select($db->quoteName($selectedFields));
                $joinedTableQuery->from($db->quoteName('#__' . $intermediateTableName, 'interm'));
                $joinedTableQuery->where($db->quoteName($localForeignKeyField) . ' = ' . $record->{$idFieldName});
                $joinedTableQuery->join('INNER', $db->quoteName('#__' . $joinedTable->name, 'remote') . ' ON ' . $db->quoteName($remoteIdField) . ' = ' . $db->quoteName($remoteForeignKeyField));
                
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
        } else if ($joinedTable->connectionType == "NToN") {
            $joinedTableOptionsQuery = $db->getQuery(true);

            $remoteIdField = $joinedTable->connectionInfo[3];

            $joinedTableOptionsQuery->select($db->quoteName([$remoteIdField, $joinedTable->displayField]));
            $joinedTableOptionsQuery->from($db->quoteName('#__' . $joinedTable->name));

            $db->setQuery($joinedTableOptionsQuery);
            $data = $db->loadObjectList();

            return $data;
        }
    }
}

<?php

// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

if ($this->getModel()->getItem()->allowEdit && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = Factory::getDbo();
    $input = Factory::getApplication()->input;

    if ($input->get('action', '', 'string') == "getItem") {
        getItem($input, $db);
    }

    if ($input->get('action', '', 'string') == "getTemplate") {
        getTemplate($input, $db);
    }

    if ($input->get('action', '', 'string') == "insert") {
        dbInsert($input, $db);
    }

    if ($input->get('action', '', 'string') == "update") {
        dbUpdate($input, $db);
    }

    if ($input->get('action', '', 'string') == "delete") {
        dbDelete($input, $db);
    }
}

public function getItem($input, $db): object {
    $templateConfigName = $input->getString('templateConfigName');

    $query = $db->getQuery(true);
    $query->select(
        $db->quoteName(['id', 'header', 'template', 'footer', 'detail_template', 'tablename', 'id_field_name', 'fields', 'condition', 'sorting', 'show_detail_page', 'allow_edit', 'allow_create', 'access', 'joined_tables'])
    );
    $query->from($db->quoteName('#__neukomtemplating_templates'));
    $query->where('name = "' . $templateConfigName . '"');
    $db->setQuery($query);
    $templateConfig = $db->loadObject();

    $user = Factory::getUser();
    $levels = $user->getAuthorisedViewLevels();

    if (!in_array((int)$templateConfig->access, $levels)) {
        throw new \Exception("Missing access levels for this template", 403);
    }

    $fields = [];
    $fieldNames = [$templateConfig->id_field_name];

    $tableFields = $db->getTableColumns('#__' . $templateConfig->tablename);

    foreach (explode(';', str_replace(' ', '', $templateConfig->fields)) as $field) {
        $fieldConfigArray = explode(':', $field);

        $fieldName = $fieldConfigArray[0];

        if ($fieldName == $templateConfig->id_field_name || !array_key_exists($fieldName, $tableFields)) {
            continue;
        }

        $fieldType = isset($fieldConfigArray[1]) ? $fieldConfigArray[1] : "text";
        $fieldRequired = isset($fieldConfigArray[2]) ? $fieldConfigArray[2] == "1" : false;
        $showFieldInForm = isset($fieldConfigArray[3]) ? $fieldConfigArray[3] == "1" : true;
        $displayName = isset($fieldConfigArray[4]) ? $fieldConfigArray[4] : $fieldName;
        $additionalInfo = isset($fieldConfigArray[5]) ? $fieldConfigArray[5] : "";

        if ($showFieldInForm) {
            $fields[] = [$fieldName, $fieldType, $fieldRequired, $showFieldInForm, $displayName, $additionalInfo];
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
    $item->detailTemplate = $templateConfig->detail_template;
    $item->showDetailPage = ($templateConfig->show_detail_page == "1");
    $item->allowEdit = ($templateConfig->allow_edit == "1");
    $item->allowCreate = ($templateConfig->allow_create == "1");
    $item->data = $data;
    
    $item->fields = $fields;
    $item->joinedTables = $joinedTables;

    return $item;
}

function getTemplate($input, $db) {
    
}

function dbInsert($input, $db) {
    $query = $db->getQuery(true);
    $item = getItem();

    if (!$item->allowCreate) {
        return;
    }

    $insertColumns = array();
    $insertValues = array();

    $validationFailed = false;

    foreach ($item->fields as $field) {
        $fieldName = $field[0];
        $fieldType = $field[1];
        $fieldRequired = $field[2];

        if ($fieldType == 'image') {
            $fieldValue = uploadFile($input, $input->get($fieldName, '', 'string'));
        } else {
            $fieldValue = $input->get($fieldName, '', 'string');
        }

        if (!validateInput($fieldValue, $fieldName, $fieldType, $fieldRequired)) {
            $validationFailed = true;
        }

        $insertColumns[] = $fieldName;
        $insertValues[] = formatInputValue($fieldValue, $fieldType, $db);
    }

    foreach ($item->joinedTables as $joinedTable) {
        if ($joinedTable->connectionType == "NToOne") {
            $foreignId = $input->get($joinedTable->name, '', 'string');

            $insertColumns[] = $joinedTable->connectionInfo[0];
            $insertValues[] = formatInputValue($foreignId, "foreignId", $db);
        }
    }

    if ($validationFailed) {return 0;}

    $query
        ->insert('#__' . $item->tableName)
        ->columns($db->quoteName($insertColumns))
        ->values(implode(',', $insertValues));

    $db->setQuery($query);
    $db->execute();

    foreach ($item->joinedTables as $joinedTable) {
        if ($joinedTable->connectionType == "NToN") {
            $localForeignKey = $db->insertid();

            foreach ($joinedTable->options as $option) {
                $remoteForeignKey = $input->get($joinedTable->name . '-' . $option->{$joinedTable->connectionInfo[3]}, '', 'string');

                if ($remoteForeignKey != '') {
                    addIntermediateEntry($db, $joinedTable, $localForeignKey, $remoteForeignKey);
                }
            }
        }
    }
}

function dbUpdate($input, $db) {
    $query = $db->getQuery(true);
    $item = getItem();

    if (!$item->allowEdit) {
        return;
    }

    $updateFields = array();

    $validationFailed = false;

    foreach ($item->fields as $field) {
        $fieldName = $field[0];
        $fieldType = $field[1];
        $fieldRequired = $field[2];

        if ($fieldType == 'image') {
            $fieldValue = uploadFile($input, $fieldName);

            if ($fieldValue == "") { continue; }
        } else {
            $fieldValue = $input->get($fieldName, '', 'string');
        }

        if (!validateInput($fieldValue, $fieldName, $fieldType, $fieldRequired)) {
            $validationFailed = true;
        }

        $updateFields[] = $db->quoteName($fieldName) . " = " . formatInputValue($fieldValue, $fieldType, $db);
    }

    foreach ($item->joinedTables as $joinedTable) {
        if ($joinedTable->connectionType == "NToOne") {
            $foreignId = $input->get($joinedTable->name, '', 'string');

            $updateFields[] = $db->quoteName($joinedTable->connectionInfo[0]) . " = " . formatInputValue($foreignId, "foreignId", $db);
        } else if ($joinedTable->connectionType == "NToN") {
            dropIntermediateEntries($db, $joinedTable, $input->get('recordId', '', 'string'));

            foreach ($joinedTable->options as $option) {
                $remoteForeignKey = $input->get($joinedTable->name . '-' . $option->{$joinedTable->connectionInfo[3]}, '', 'string');

                if ($remoteForeignKey != '') {
                    addIntermediateEntry($db, $joinedTable, $input->get('recordId', '', 'string'), $remoteForeignKey);
                }
            }
        }
    }

    if ($validationFailed) {return 0;}

    $updateConditions = array(
        $db->quoteName($item->idFieldName) . ' = ' . $input->get('recordId', '', 'string')
    );

    $query
        ->update('#__' . $item->tableName)
        ->set($updateFields)
        ->where($updateConditions);

    $db->setQuery($query);

    $result = $db->execute();
}

function dbDelete($input, $db) {
    $query = $db->getQuery(true);
    $item = getItem();

    if (!$item->allowEdit) {
        return;
    }

    $deleteConditions = array($db->quoteName($item->idFieldName) . " = " . $input->get('recordId', '', 'string'));

    $query
        ->delete('#__' . $item->tableName)
        ->where($deleteConditions);

    $db->setQuery($query);

    $result = $db->execute();

    foreach ($item->joinedTables as $joinedTable) {
        if ($joinedTable->connectionType == "NToN") {
            dropIntermediateEntries($db, $joinedTable, $input->get('recordId', '', 'string'));
        }
    }
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
    $db = Factory::getDbo();

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
    $db = Factory::getDbo();

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

function validateInputFormat($value, $type) {
    $validationPatterns = array(
        'text' => "/.*/",
        'textarea' => "/(?s).*/",
        'date' => "/^\d{4}\-(0?[1-9]|1[012])\-(0?[1-9]|[12][0-9]|3[01])$/",
        'number' => "/^[0-9]*$/",
        'checkbox' => "/^(on)?$/",
        'select' => "/.*/",
        'image' => "/.+\\.(png|jpg|gif|bmp|jpeg|PNG|JPG|GIF|BMP|JPEG)$/",
    );

    return preg_match($validationPatterns[$type], $value);
}

function validateInput($value, $name, $type, $required) {
    if ($required && $value == '') {
        JFactory::getApplication()->enqueueMessage("Validierungsfehler: $name kann nicht leer sein", 'error');
        return false;
    } 
    
    if (!$required && $value == '') {
        return true;
    }

    if (!validateInputFormat($value, $type)) {
        JFactory::getApplication()->enqueueMessage("Validierungsfehler: $value passt nicht zum Format $type", 'error');
        return false;
    }

    return true;
}

function formatInputValue($value, $type, $db) {
    switch ($type) {
        case "number":
            return ($value == "" ? "NULL" : $db->quote($value));
        case "date":
            return ($value == "" ? "NULL" : $db->quote($value));
        case "checkbox":
            return ($value == "on" ? "'1'" : "'0'");
        case "foreignId":
            return ($value == "0" ? "NULL" : $db->quote($value));
        default:
            return $db->quote($value);
    }
}

function dropIntermediateEntries($db, $joinedTable, $localForeignKey) {
    $query = $db->getQuery(true);

    $deleteConditions = array($db->quoteName($joinedTable->connectionInfo[1]) . " = " . $localForeignKey);

    $query
        ->delete('#__' . $joinedTable->connectionInfo[0])
        ->where($deleteConditions);

    $db->setQuery($query);

    $result = $db->execute();
}

function addIntermediateEntry($db, $joinedTable, $localForeignKey, $remoteForeignKey) {
    $query = $db->getQuery(true);

    $insertColumns = [$joinedTable->connectionInfo[1], $joinedTable->connectionInfo[2]];
    $insertValues = [$localForeignKey, $remoteForeignKey];

    $query
        ->insert('#__' . $joinedTable->connectionInfo[0])
        ->columns($db->quoteName($insertColumns))
        ->values(implode(',', $insertValues));

    $db->setQuery($query);
    $db->execute();
}

function uploadFile($input, $fieldName) {
    $file = $input->files->get($fieldName);

    if (is_null($file) || $file['tmp_name'] == "") {
        return "";
    }

    $pathParts = pathinfo($file['name']);

    $filename = File::makeSafe($pathParts['filename'] . "_" . date('Ymdis') . "." . $pathParts['extension']);

    $source  = $file['tmp_name'];
    $destination = JPATH_SITE . "/images/imageuploads/" . $filename;
    
    if (JFile::upload($source, $destination)) {
        return $filename;
    } else {
        error_log("Failed to upload " . $source . " to " . $destination);
        return "";
    }
}

?>

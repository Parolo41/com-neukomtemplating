<?php

namespace Neukom\Component\NeukomTemplating\Site\Helper;

defined('_JEXEC') or die;

use Joomla\Filesystem\File;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;

class Helper {
    private static ?Helper $instance = null;

    private $model;
    private $db;

    protected function __construct() {
        $this->model = Factory::getApplication()
            ->bootComponent('com_neukomtemplating')
            ->getMVCFactory()
            ->createModel('Template', 'Site');

        $this->db = Factory::getContainer()->get('DatabaseDriver');
    }

    public static function getInstance(): Helper {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
    
    public function dbInsert($input) {
        $query = $this->db->getQuery(true);
        $item = $this->model->getItem();
    
        if (!$item->allowCreate || $item->userIdLinkField != "") {
            return 0;
        }
    
        $insertColumns = array();
        $insertValues = array();
        $fieldLabels = array();
        $fieldValues = array();
    
        $validationFailed = false;
    
        foreach ($item->fields as $field) {
            if ($field['type'] == 'image') {
                $fieldValue = $this->uploadFile($input, $field, "/images/imageuploads/");
            } elseif ($field['type'] == 'pdf') {
                $fieldValue = $this->uploadFile($input, $field, "/images/documentuploads/");
            } elseif ($field['type'] == 'texteditor') {
                $fieldValue = $input->get($field['name'], '', 'raw');
            } elseif (array_key_exists($field['type'], $item->aliases)) {
                $fieldValue = strval($item->aliases[$field['type']]);
            } else {
                $fieldValue = $input->get($field['name'], '', 'string');
            }
    
            if (!array_key_exists($field['type'], $item->aliases) && !$this->validateInput($fieldValue, $field)) {
                $validationFailed = true;
            }

            $dbType = empty($item->tableFields[$field['name']]) ? $field['type'] : $item->tableFields[$field['name']];

            $insertColumns[] = $field['name'];
            $insertValues[] = $this->formatInputValue($fieldValue, $field, $dbType);
            $fieldLabels[] = $field['label'];
        }
    
        $fieldValues = $insertValues;
    
        foreach ($item->urlDbInserts as $urlDbInsert) {
            $insertColumns[] = $urlDbInsert;
            $insertValues[] = $this->db->quote($item->urlParameters[$urlDbInsert]);
        }
    
        foreach ($item->joinedTables as $joinedTable) {
            if ($joinedTable['connectionType'] == "NToOne") {
                $foreignId = $input->get($joinedTable['alias'], '', 'string');
    
                $insertColumns[] = $joinedTable['NToOne-foreignKey'];
                $insertValues[] = $this->formatInputValue($foreignId, array( 'type' => 'foreignId' ), 'foreignKey');
                $fieldLabels[] = $joinedTable['formName'];
                $fieldValues[] = $joinedTable['options'][$foreignId]->{$joinedTable['displayField']};
            }
        }
    
        if ($validationFailed) {return 0;}
    
        $query
            ->insert('#__' . $item->tableName)
            ->columns($this->db->quoteName($insertColumns))
            ->values(implode(',', $insertValues));
    
        $this->db->setQuery($query);
        $this->db->execute();
    
        $lastRowId = $this->db->insertId();
    
        foreach ($item->joinedTables as $joinedTable) {
            if ($joinedTable['connectionType'] == "NToN") {
                $localForeignKey = $lastRowId;
    
                $selectedOptions = array();
    
                foreach ($joinedTable['options'] as $option) {
                    $remoteForeignKey = $input->get($joinedTable['alias'] . '-' . $option->{$joinedTable['NToN-remoteId']}, '', 'string');
    
                    if ($remoteForeignKey != '') {
                        $this->addIntermediateEntry($joinedTable, $localForeignKey, $remoteForeignKey);
                        $selectedOptions[] = $remoteForeignKey;
                    }
    
                    $fieldLabels = $joinedTable['formName'];
                    $fieldValues = implode(', ', $selectedOptions);
                }
            }
        }
    
        if ($item->notificationTrigger == 'on_new' || $item->notificationTrigger == 'both') {
            $this->sendNotification("Neuer Eintrag", $fieldLabels, $fieldValues, $item);
        }
    
        return $lastRowId;
    }
    
    public function dbUpdate($input) {
        $query = $this->db->getQuery(true);
        $item = $this->model->getItem();
        $recordId = $input->get('recordId', 0, 'INT');
    
        if (!$item->allowEdit) {
            return 0;
        }
    
        $updateFields = array();
        $fieldLabels = array();
        $fieldValues = array();
    
        $validationFailed = false;
    
        foreach ($item->fields as $field) {
            if ($field['type'] == 'image') {
                if ($input->get($field['name'] . '-delete', '', 'string') == 'on') {
                    $fieldValue = '';
                } else {
                    $fieldValue = $this->uploadFile($input, $field, "/images/imageuploads/");
    
                    if ($fieldValue == "") { continue; }
                }
            } elseif ($field['type'] == 'pdf') {
                if ($input->get($field['name'] . '-delete', '', 'string') == '1') {
                    $fieldValue = '';
                } else {
                    $fieldValue = $this->uploadFile($input, $field, "/images/documentuploads/");
    
                    if ($fieldValue == "") { continue; }
                }
            } elseif ($field['type'] == 'texteditor') {
                $fieldValue = $input->get($field['name'], '', 'raw');
            } elseif (array_key_exists($field['type'], $item->aliases)) {
                $fieldValue = strval($item->aliases[$field['type']]);
            } else {
                $fieldValue = $input->get($field['name'], '', 'string');
            }
    
            if (!array_key_exists($field['type'], $item->aliases) && !$this->validateInput($fieldValue, $field)) {
                $validationFailed = true;
            }

            $dbType = empty($item->tableFields[$field['name']]) ? $field['type'] : $item->tableFields[$field['name']];
    
            $formattedValue = $this->formatInputValue($fieldValue, $field, $dbType);
    
            $updateFields[] = $this->db->quoteName($field['name']) . " = " . $formattedValue;
            $fieldLabels[] = $field['label'];
            $fieldValues[] = $formattedValue;
        }
    
        foreach ($item->urlDbInserts as $urlDbInsert) {
            $updateFields[] = $this->db->quoteName($urlDbInsert) . " = " . $this->db->quote($item->urlParameters[$urlDbInsert]);
        }
    
        if ($validationFailed) {return 0;}
    
        foreach ($item->joinedTables as $joinedTable) {
            if ($joinedTable['connectionType'] == "NToOne") {
                $foreignId = $input->get($joinedTable['alias'], '', 'string');
    
                $updateFields[] = $this->db->quoteName($joinedTable['NToOne-foreignKey']) . " = " . $this->formatInputValue($foreignId, array( 'type' => 'foreignId' ), 'foreignKey');
                $fieldLabels[] = $joinedTable['formName'];
                $fieldValues[] = $joinedTable['options'][$foreignId]->{$joinedTable['displayField']};
            } else if ($joinedTable['connectionType'] == "NToN") {
                $currentIds = array_column($item->data[$recordId]->{$joinedTable['alias']}, $joinedTable['NToN-remoteId']);
    
                $selectedOptions = array();
    
                foreach ($joinedTable['options'] as $option) {
                    $optionId = $option->{$joinedTable['NToN-remoteId']};
                    $remoteForeignKey = $input->get($joinedTable['alias'] . '-' . $optionId, '', 'string');
    
                    if ($remoteForeignKey != '' && !in_array($optionId, $currentIds)) {
                        $this->addIntermediateEntry($joinedTable, $recordId, $remoteForeignKey);
                    } else if ($remoteForeignKey == '' && in_array($optionId, $currentIds)) {
                        $this->dropIntermediateEntry($joinedTable, $recordId, $optionId);
                    }
    
                    if ($remoteForeignKey != '') {
                        $selectedOptions[] = $remoteForeignKey;
                    }
    
                    $fieldLabels = $joinedTable['formName'];
                    $fieldValues = implode(', ', $selectedOptions);
                }
            }
        }
    
        $updateConditions = array(
            $this->db->quoteName($item->idFieldName) . ' = ' . $recordId
        );

        if ($item->userIdLinkField != "") {
            $user = Factory::getUser();
            $updateConditions[] = $this->db->quoteName($item->userIdLinkField) . ' = ' . $user->id;
        }
    
        $query
            ->update('#__' . $item->tableName)
            ->set($updateFields)
            ->where($updateConditions);
    
        $this->db->setQuery($query);
    
        $result = $this->db->execute();
    
        if ($item->notificationTrigger == 'on_edit' || $item->notificationTrigger == 'both') {
            $this->sendNotification("Eintrag Bearbeitet", $fieldLabels, $fieldValues, $item);
        }
    
        return 1;
    }
    
    public function dbDelete($input) {
        $query = $this->db->getQuery(true);
        $item = $this->model->getItem();
    
        if (!$item->allowEdit || $item->userIdLinkField != "") {
            return 0;
        }
    
        $deleteConditions = array($this->db->quoteName($item->idFieldName) . " = " . $input->get('recordId', 0, 'INT'));
    
        $query
            ->delete('#__' . $item->tableName)
            ->where($deleteConditions);
    
        $this->db->setQuery($query);
    
        $result = $this->db->execute();
    
        foreach ($item->joinedTables as $joinedTable) {
            if ($joinedTable['connectionType'] == "NToN") {
                $this->dropIntermediateEntries($joinedTable, $input->get('recordId', 0, 'INT'));
            }
        }
    
        return 1;
    }
    
    public function sendMessage($input) {
        $item = $this->model->getItem();
        $recordId = $input->get('recordId', 0, 'INT');
    
        $senderName = $input->get('sender-name', '', 'string');
        $senderEmail = $input->get('sender-email', '', 'string');
        $messageSubject = $input->get('message-subject', '', 'string');
        $messageBody = $input->get('message-body', '', 'string');
    
        if (empty($item->data[$recordId])
            || $item->contactEmailField == "" 
            || empty($item->data[$recordId]->{$item->contactEmailField})
            || !filter_var($item->data[$recordId]->{$item->contactEmailField}, FILTER_VALIDATE_EMAIL)
            || !filter_var($senderEmail, FILTER_VALIDATE_EMAIL) || strlen($senderEmail) > 100
            || empty($senderName) || strlen($senderName) > 50
            || empty($messageSubject) || strlen($messageSubject) > 50
            || empty($messageBody) || strlen($messageBody) > 500) {
            return 0;
        }
    
        $to      = $item->data[$recordId]->{$item->contactEmailField};
        $subject = Text::_('COM_NEUKOMTEMPLATING_CONTACT_FROM') . $input->get('sender-name', '', 'string');
        $headers = 'From: ' . $input->get('sender-email', '', 'string') . "\r\n" .
            'Reply-To: ' . $input->get('sender-email', '', 'string') . "\r\n" .
            'X-Mailer: PHP/' . phpversion();
        
        $message = "<table>";
        $message .= "<tr><th>" . Text::_('COM_NEUKOMTEMPLATING_CONTACT_SENDER_NAME') . "</th><td>" . $input->get('sender-name', '', 'string') . "</td></tr>";
        $message .= "<tr><th>" . Text::_('COM_NEUKOMTEMPLATING_CONTACT_SENDER_EMAIL') . "</th><td>" . $input->get('sender-email', '', 'string') . "</td></tr>";
        $message .= "<tr><th>" . Text::_('COM_NEUKOMTEMPLATING_CONTACT_MESSAGE_SUBJECT') . "</th><td>" . $input->get('message-subject', '', 'string') . "</td></tr>";
        $message .= "<tr><th>" . Text::_('COM_NEUKOMTEMPLATING_CONTACT_MESSAGE_BODY') . "</th><td>" . wordwrap($input->get('message-body', '', 'string')) . "</td></tr>";
        $message .= "</table>";
        
        mail($to, $subject, $message, $headers);
    
        return 1;
    }

    public function buildUrl($target, $recordId = '', $targetPage = 0) {
        $item = $this->model->getItem();
        $input = Factory::getApplication()->input;
        $query = array('option' => 'com_neukomtemplating', 'view' => 'main', 'Itemid' => 108);
    
        switch ($target) {
            case 'list':
                break;
            case 'detail':
                $query['act'] = 'detail';
                $query['recordId'] = $recordId;
                break;
            case 'new':
                $query['act'] = 'new';
                break;
            case 'edit':
                $query['act'] = 'edit';
                $query['recordId'] = $recordId;
                break;
            case 'contact':
                $query['act'] = 'contact';
                $query['recordId'] = $recordId;
                break;
        }
    
        $searchTerm = $input->get('searchTerm', '', 'string');
    
        if ($searchTerm != '') {
            $query['searchTerm'] = $searchTerm;
        }
    
        $pageNumber = $input->get('pageNumber', 0, 'int');
    
        if ($targetPage != 0) {
            $query['pageNumber'] = $targetPage;
        } elseif ($pageNumber != 0) {
            $query['pageNumber'] = $pageNumber;
        }
    
        foreach ($item->urlParameters as $parameterName => $urlParameter) {
            $parameterValue = $input->get($parameterName, false, 'string');
    
            if ($parameterValue != false) {
                $query[$parameterName] = $parameterValue;
            }
        }

        $link = Uri::base();
        
        $app = Factory::getApplication();
        $activeMenuitem = $app->getMenu()->getActive();

        if (str_contains(Uri::current(), 'index.php')) {
            $link .= 'index.php';

            if (!empty($activeMenuitem->route)) {
                $link .= '/';
            }
        }

        if (!empty($activeMenuitem->route)) {
            $link .= $activeMenuitem->route;
        }

        if (!empty($query)) {
            $link .= '?' . Uri::buildQuery($query);
        }
    
        return Route::link('site', $link);
    }
    
    public function setUrl($url) {
        echo "<script>history.replaceState({},'','$url');</script>";
    }

    private function validateInputFormat($value, $field) {
        $validationPatterns = array(
            'text' => "/.*/",
            'textarea' => "/(?s).*/",
            'texteditor' => "/(?s).*/",
            'date' => "/^\d{4}\-(0?[1-9]|1[012])\-(0?[1-9]|[12][0-9]|3[01])$/",
            'time' => "/^[0-2]?[0-9]:[0-5][0-9](:[0-5][0-9])?$/",
            'number' => "/^[0-9]*$/",
            'checkbox' => "/^(on)?$/",
            'select' => "/.*/",
            'image' => "/.+\\.(png|jpg|gif|bmp|jpeg|PNG|JPG|GIF|BMP|JPEG)$/",
            'pdf' => "/.+\\.(pdf|PDF)$/",
        );
    
        return preg_match($validationPatterns[$field['type']], $value);
    }
    
    private function validateInput($value, $field) {
        if ($field['required'] && $value == '') {
            Factory::getApplication()->enqueueMessage(sprintf(Text::_('COM_NEUKOMTEMPLATING_ERROR_EMPTY'), $field['name']), 'error');
            return false;
        } 
        
        if (!$field['required'] && $value == '') {
            return true;
        }
    
        if (!$this->validateInputFormat($value, $field)) {
            Factory::getApplication()->enqueueMessage(sprintf(Text::_('COM_NEUKOMTEMPLATING_ERROR_FORMAT'), $field['name'], $field['type']), 'error');
            return false;
        }
    
        return true;
    }
    
    private function formatInputValue($value, $field, $dbType) {
        if ($value == "" && in_array($dbType, ['date', 'time', 'datetime']) && $field['type'] != "checkbox") {
            return "NULL";
        }
    
        switch ($field['type']) {
            case "number":
                if ($value == "" && in_array($dbType, ['varchar', 'text'])) {
                    return $this->db->quote($value);
                }
    
                return ($value == "" ? "NULL" : $this->db->quote($value));
            case "date":
                if ($value == "" && in_array($dbType, ['varchar', 'text'])) {
                    return $this->db->quote($value);
                }
    
                return ($value == "" ? "NULL" : $this->db->quote($value));
            case "time":
                if ($value == "" && in_array($dbType, ['varchar', 'text'])) {
                    return $this->db->quote($value);
                }
    
                return ($value == "" ? "NULL" : $this->db->quote($value));
            case "checkbox":
                return ($value == "on" ? "'1'" : "'0'");
            case "foreignId":
                return ($value == "NULL" ? "NULL" : $this->db->quote($value));
            default:
                return $this->db->quote($value);
        }
    }
    
    private function addIntermediateEntry($joinedTable, $localForeignKey, $remoteForeignKey) {
        $query = $this->db->getQuery(true);
    
        $insertColumns = [$joinedTable['NToN-intermediateLocalKey'], $joinedTable['NToN-intermediateRemoteKey']];
        $insertValues = [$localForeignKey, $remoteForeignKey];
    
        $query
            ->insert('#__' . $joinedTable['NToN-intermediateTable'])
            ->columns($this->db->quoteName($insertColumns))
            ->values(implode(',', $insertValues));
    
        $this->db->setQuery($query);
        $this->db->execute();
    }
    
    private function dropIntermediateEntries($joinedTable, $localForeignKey) {
        $query = $this->db->getQuery(true);
    
        $deleteConditions = array($this->db->quoteName($joinedTable['NToN-intermediateLocalKey']) . " = " . $localForeignKey);
    
        $query
            ->delete('#__' . $joinedTable['NToN-intermediateTable'])
            ->where($deleteConditions);
    
        $this->db->setQuery($query);
    
        $result = $this->db->execute();
    }
    
    private function dropIntermediateEntry($joinedTable, $localForeignKey, $remoteForeignKey) {
        $query = $this->db->getQuery(true);
    
        $deleteConditions = array(
            $this->db->quoteName($joinedTable['NToN-intermediateLocalKey']) . " = " . $localForeignKey,
            $this->db->quoteName($joinedTable['NToN-intermediateRemoteKey']) . " = " . $remoteForeignKey
        );
    
        $query
            ->delete('#__' . $joinedTable['NToN-intermediateTable'])
            ->where($deleteConditions);
    
        $this->db->setQuery($query);
    
        $result = $this->db->execute();
    }
    
    private function uploadFile($input, $field, $subFolder) {
        $file = $input->files->get($field['name']);
    
    
        if (is_null($file) || $file['tmp_name'] == "") {
            error_log("File not found: " . $field['name']);
            return "";
        }
    
        $pathParts = pathinfo($file['name']);
    
        $filename = File::makeSafe($pathParts['filename'] . "_" . date('Ymdis') . "." . $pathParts['extension']);
    
        $source  = $file['tmp_name'];
        $destination = JPATH_SITE . $subFolder . $filename;
        
        if (File::upload($source, $destination)) {
            return $filename;
        } else {
            error_log("Failed to upload " . $source . " to " . $destination);
            return "";
        }
    }
    
    private function sendNotification($subject, $fieldLabels, $fieldValues, $item) {
        if ($item->notificationRecipients == '') {
            return;
        }
        
        $message = "<table>";
        
        for ($i = 0; $i < count($fieldLabels); $i++) {
            $message .= "<tr><th>" . $fieldLabels[$i] . "</th><td>" . $fieldValues[$i] . "</td></tr>";
        }
        
        $message .= "</table>";
    
        $recipients = explode(',', $item->notificationRecipients);
    
        foreach ($recipients as $recipient) {
            if (!filter_var(trim($recipient), FILTER_VALIDATE_EMAIL)) {
                continue;
            }
    
            mail(trim($recipient), $subject, $message);
        }
    }
}
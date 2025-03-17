<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

<?php
use Joomla\Filesystem\File;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

function validateInputFormat($value, $type) {
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

    return preg_match($validationPatterns[$type], $value);
}

function validateInput($value, $name, $type, $required) {
    if ($required && $value == '') {
        Factory::getApplication()->enqueueMessage(sprintf(Text::_('COM_NEUKOMTEMPLATING_ERROR_EMPTY'), $name), 'error');
        return false;
    } 
    
    if (!$required && $value == '') {
        return true;
    }

    if (!validateInputFormat($value, $type)) {
        Factory::getApplication()->enqueueMessage(sprintf(Text::_('COM_NEUKOMTEMPLATING_ERROR_FORMAT'), $name, $type), 'error');
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
        case "time":
            return ($value == "" ? "NULL" : $db->quote($value));
        case "checkbox":
            return ($value == "on" ? "'1'" : "'0'");
        case "foreignId":
            return ($value == "NULL" ? "NULL" : $db->quote($value));
        default:
            return $db->quote($value);
    }
}

function dbInsert($input, $db, $self) {
    $query = $db->getQuery(true);
    $item = $self->getModel()->getItem();

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
            $fieldValue = uploadFile($input, $field['name'], "/images/imageuploads/");
        } elseif ($field['type'] == 'pdf') {
            $fieldValue = uploadFile($input, $field['name'], "/images/documentuploads/");
        } elseif ($field['type'] == 'texteditor') {
            $fieldValue = $input->get($field['name'], '', 'raw');
        } elseif (array_key_exists($field['type'], $item->aliases)) {
            $fieldValue = strval($item->aliases[$field['type']]);
        } else {
            $fieldValue = $input->get($field['name'], '', 'string');
        }

        if (!array_key_exists($field['type'], $item->aliases) && !validateInput($fieldValue, $field['name'], $field['type'], $field['required'])) {
            $validationFailed = true;
        }

        $insertColumns[] = $field['name'];
        $insertValues[] = formatInputValue($fieldValue, $field['type'], $db);
        $fieldLabels[] = $field['label'];
    }

    $fieldValues = $insertValues;

    foreach ($item->urlDbInserts as $urlDbInsert) {
        $insertColumns[] = $urlDbInsert;
        $insertValues[] = $db->quote($item->urlParameters[$urlDbInsert]);
    }

    foreach ($item->joinedTables as $joinedTable) {
        if ($joinedTable['connectionType'] == "NToOne") {
            $foreignId = $input->get($joinedTable['alias'], '', 'string');

            $insertColumns[] = $joinedTable['NToOne-foreignKey'];
            $insertValues[] = formatInputValue($foreignId, "foreignId", $db);
            $fieldLabels[] = $joinedTable['formName'];
            $fieldValues[] = $joinedTable['options'][$foreignId]->{$joinedTable['displayField']};
        }
    }

    if ($validationFailed) {return 0;}

    $query
        ->insert('#__' . $item->tableName)
        ->columns($db->quoteName($insertColumns))
        ->values(implode(',', $insertValues));

    $db->setQuery($query);
    $db->execute();

    $lastRowId = $db->insertId();

    foreach ($item->joinedTables as $joinedTable) {
        if ($joinedTable['connectionType'] == "NToN") {
            $localForeignKey = $lastRowId;

            $selectedOptions = array();

            foreach ($joinedTable['options'] as $option) {
                $remoteForeignKey = $input->get($joinedTable['alias'] . '-' . $option->{$joinedTable['NToN-remoteId']}, '', 'string');

                if ($remoteForeignKey != '') {
                    addIntermediateEntry($db, $joinedTable, $localForeignKey, $remoteForeignKey);
                    $selectedOptions[] = $remoteForeignKey;
                }

                $fieldLabels = $joinedTable['formName'];
                $fieldValues = implode(', ', $selectedOptions);
            }
        }
    }

    if ($item->notificationTrigger == 'on_new' || $item->notificationTrigger == 'both') {
        sendNotification("Neuer Eintrag", $fieldLabels, $fieldValues, $item);
    }

    return $lastRowId;
}

function dbUpdate($input, $db, $self) {
    $query = $db->getQuery(true);
    $item = $self->getModel()->getItem();
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
                $fieldValue = uploadFile($input, $field['name'], "/images/imageuploads/");

                if ($fieldValue == "") { continue; }
            }
        } elseif ($field['type'] == 'pdf') {
            if ($input->get($field['name'] . '-delete', '', 'string') == '1') {
                $fieldValue = '';
            } else {
                $fieldValue = uploadFile($input, $field['name'], "/images/documentuploads/");

                if ($fieldValue == "") { continue; }
            }
        } elseif ($field['type'] == 'texteditor') {
            $fieldValue = $input->get($field['name'], '', 'raw');
        } elseif (array_key_exists($field['type'], $item->aliases)) {
            $fieldValue = strval($item->aliases[$field['type']]);
        } else {
            $fieldValue = $input->get($field['name'], '', 'string');
        }

        if (!array_key_exists($field['type'], $item->aliases) && !validateInput($fieldValue, $field['name'], $field['type'], $field['required'])) {
            $validationFailed = true;
        }

        $formattedValue = formatInputValue($fieldValue, $field['type'], $db);

        $updateFields[] = $db->quoteName($field['name']) . " = " . $formattedValue;
        $fieldLabels[] = $field['label'];
        $fieldValues[] = $formattedValue;
    }

    foreach ($item->urlDbInserts as $urlDbInsert) {
        $updateFields[] = $db->quoteName($urlDbInsert) . " = " . $db->quote($item->urlParameters[$urlDbInsert]);
    }

    if ($validationFailed) {return 0;}

    foreach ($item->joinedTables as $joinedTable) {
        if ($joinedTable['connectionType'] == "NToOne") {
            $foreignId = $input->get($joinedTable['alias'], '', 'string');

            $updateFields[] = $db->quoteName($joinedTable['NToOne-foreignKey']) . " = " . formatInputValue($foreignId, "foreignId", $db);
            $fieldLabels[] = $joinedTable['formName'];
            $fieldValues[] = $joinedTable['options'][$foreignId]->{$joinedTable['displayField']};
        } else if ($joinedTable['connectionType'] == "NToN") {
            $currentIds = array_column($item->data[$recordId]->{$joinedTable['alias']}, $joinedTable['NToN-remoteId']);

            $selectedOptions = array();

            foreach ($joinedTable['options'] as $option) {
                $optionId = $option->{$joinedTable['NToN-remoteId']};
                $remoteForeignKey = $input->get($joinedTable['alias'] . '-' . $optionId, '', 'string');

                if ($remoteForeignKey != '' && !in_array($optionId, $currentIds)) {
                    addIntermediateEntry($db, $joinedTable, $recordId, $remoteForeignKey);
                } else if ($remoteForeignKey == '' && in_array($optionId, $currentIds)) {
                    dropIntermediateEntry($db, $joinedTable, $recordId, $optionId);
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
        $db->quoteName($item->idFieldName) . ' = ' . $recordId
    );

    $query
        ->update('#__' . $item->tableName)
        ->set($updateFields)
        ->where($updateConditions);

    $db->setQuery($query);

    $result = $db->execute();

    if ($item->notificationTrigger == 'on_edit' || $item->notificationTrigger == 'both') {
        sendNotification("Eintrag Bearbeitet", $fieldLabels, $fieldValues, $item);
    }

    return 1;
}

function dbDelete($input, $db, $self) {
    $query = $db->getQuery(true);
    $item = $self->getModel()->getItem();

    if (!$item->allowEdit || $item->userIdLinkField != "") {
        return 0;
    }

    $deleteConditions = array($db->quoteName($item->idFieldName) . " = " . $input->get('recordId', 0, 'INT'));

    $query
        ->delete('#__' . $item->tableName)
        ->where($deleteConditions);

    $db->setQuery($query);

    $result = $db->execute();

    foreach ($item->joinedTables as $joinedTable) {
        if ($joinedTable['connectionType'] == "NToN") {
            dropIntermediateEntries($db, $joinedTable, $input->get('recordId', 0, 'INT'));
        }
    }

    return 1;
}

function sendMessage($input, $db, $self) {
    $item = $self->getModel()->getItem();
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

function addIntermediateEntry($db, $joinedTable, $localForeignKey, $remoteForeignKey) {
    $query = $db->getQuery(true);

    $insertColumns = [$joinedTable['NToN-intermediateLocalKey'], $joinedTable['NToN-intermediateRemoteKey']];
    $insertValues = [$localForeignKey, $remoteForeignKey];

    $query
        ->insert('#__' . $joinedTable['NToN-intermediateTable'])
        ->columns($db->quoteName($insertColumns))
        ->values(implode(',', $insertValues));

    $db->setQuery($query);
    $db->execute();
}

function dropIntermediateEntries($db, $joinedTable, $localForeignKey) {
    $query = $db->getQuery(true);

    $deleteConditions = array($db->quoteName($joinedTable['NToN-intermediateLocalKey']) . " = " . $localForeignKey);

    $query
        ->delete('#__' . $joinedTable['NToN-intermediateTable'])
        ->where($deleteConditions);

    $db->setQuery($query);

    $result = $db->execute();
}

function dropIntermediateEntry($db, $joinedTable, $localForeignKey, $remoteForeignKey) {
    $query = $db->getQuery(true);

    $deleteConditions = array(
        $db->quoteName($joinedTable['NToN-intermediateLocalKey']) . " = " . $localForeignKey,
        $db->quoteName($joinedTable['NToN-intermediateRemoteKey']) . " = " . $remoteForeignKey
    );

    $query
        ->delete('#__' . $joinedTable['NToN-intermediateTable'])
        ->where($deleteConditions);

    $db->setQuery($query);

    $result = $db->execute();
}

function uploadFile($input, $fieldName, $subFolder) {
    $file = $input->files->get($fieldName);


    if (is_null($file) || $file['tmp_name'] == "") {
        error_log("File not found: " . $fieldName);
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

function sendNotification($subject, $fieldLabels, $fieldValues, $item) {
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

function buildUrl($self, $target, $recordId = '', $targetPage = 0) {
    $item = $self->getModel()->getItem();
    $input = Factory::getApplication()->input;
    $query = array();

    switch ($target) {
        case 'list':
            $query['act'] = 'list';
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

    return Uri::current() . '?' . Uri::buildQuery($query);
}

function setUrl($url) {
    echo "<script>history.replaceState({},'','$url');</script>";
}
?>

<script>
    <?php if ($item->allowEdit) { ?>

    function confirmDelete() {
        document.getElementById("formAction").value = 'delete';

        document.getElementById("neukomtemplating-formbuttons").style.display = 'none';
        document.getElementById("neukomtemplating-deletebuttons").style.display = 'block';
    }

    function cancelDelete() {
        document.getElementById("formAction").value = "update";

        document.getElementById("neukomtemplating-formbuttons").style.display = 'block';
        document.getElementById("neukomtemplating-deletebuttons").style.display = 'none';
    }

    <?php } ?>
</script>

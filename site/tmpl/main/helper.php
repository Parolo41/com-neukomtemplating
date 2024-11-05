<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

<?php
use Joomla\Filesystem\File;

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
        JFactory::getApplication()->enqueueMessage(sprintf(Text::_('COM_NEUKOMTEMPLATING_ERROR_EMPTY'), $name), 'error');
        return false;
    } 
    
    if (!$required && $value == '') {
        return true;
    }

    if (!validateInputFormat($value, $type)) {
        JFactory::getApplication()->enqueueMessage(sprintf(Text::_('COM_NEUKOMTEMPLATING_ERROR_FORMAT'), $name, $type), 'error');
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

    $validationFailed = false;

    foreach ($item->fields as $field) {
        $fieldName = $field[0];
        $fieldType = $field[1];
        $fieldRequired = $field[2];

        if ($fieldType == 'image') {
            $fieldValue = uploadFile($input, $fieldName, "/images/imageuploads/");
        } elseif ($fieldType == 'pdf') {
            $fieldValue = uploadFile($input, $fieldName, "/images/documentuploads/");
        } elseif ($fieldType == 'texteditor') {
            $fieldValue = $input->get($fieldName, '', 'raw');
        } elseif (array_key_exists($fieldType, $item->aliases)) {
            $fieldValue = strval($item->aliases[$fieldType]);
        } else {
            $fieldValue = $input->get($fieldName, '', 'string');
        }

        if (!array_key_exists($fieldType, $item->aliases) && !validateInput($fieldValue, $fieldName, $fieldType, $fieldRequired)) {
            $validationFailed = true;
        }

        $insertColumns[] = $fieldName;
        $insertValues[] = formatInputValue($fieldValue, $fieldType, $db);
    }

    foreach ($item->urlDbInserts as $urlDbInsert) {
        $insertColumns[] = $urlDbInsert;
        $insertValues[] = $db->quote($item->urlParameters[$urlDbInsert]);
    }

    foreach ($item->joinedTables as $joinedTable) {
        if ($joinedTable->connectionType == "NToOne") {
            $foreignId = $input->get($joinedTable->alias, '', 'string');

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

    $lastRowId = $db->insertId();

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

    return $lastRowId;
}

function dbUpdate($input, $db, $self) {
    $query = $db->getQuery(true);
    $item = $self->getModel()->getItem();

    if (!$item->allowEdit) {
        return 0;
    }

    $updateFields = array();

    $validationFailed = false;

    foreach ($item->fields as $field) {
        $fieldName = $field[0];
        $fieldType = $field[1];
        $fieldRequired = $field[2];

        if ($fieldType == 'image') {
            $fieldValue = uploadFile($input, $fieldName, "/images/imageuploads/");

            if ($fieldValue == "") { continue; }
        } elseif ($fieldType == 'pdf') {
            $fieldValue = uploadFile($input, $fieldName, "/images/documentuploads/");

            if ($fieldValue == "") { continue; }
        } elseif ($fieldType == 'texteditor') {
            $fieldValue = $input->get($fieldName, '', 'raw');
        } elseif (array_key_exists($fieldType, $item->aliases)) {
            $fieldValue = strval($item->aliases[$fieldType]);
        } else {
            $fieldValue = $input->get($fieldName, '', 'string');
        }

        if (!array_key_exists($fieldType, $item->aliases) && !validateInput($fieldValue, $fieldName, $fieldType, $fieldRequired)) {
            $validationFailed = true;
        }

        $updateFields[] = $db->quoteName($fieldName) . " = " . formatInputValue($fieldValue, $fieldType, $db);
    }

    foreach ($item->urlDbInserts as $urlDbInsert) {
        $updateFields[] = $db->quoteName($urlDbInsert) . " = " . $db->quote($item->urlParameters[$urlDbInsert]);
    }

    foreach ($item->joinedTables as $joinedTable) {
        if ($joinedTable->connectionType == "NToOne") {
            $foreignId = $input->get($joinedTable->alias, '', 'string');

            $updateFields[] = $db->quoteName($joinedTable->connectionInfo[0]) . " = " . formatInputValue($foreignId, "foreignId", $db);
        } else if ($joinedTable->connectionType == "NToN") {
            dropIntermediateEntries($db, $joinedTable, $input->get('recordId', '', 'string'));

            foreach ($joinedTable->options as $option) {
                $remoteForeignKey = $input->get($joinedTable->alias . '-' . $option->{$joinedTable->connectionInfo[3]}, '', 'string');

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

    return 1;
}

function dbDelete($input, $db, $self) {
    $query = $db->getQuery(true);
    $item = $self->getModel()->getItem();

    if (!$item->allowEdit || $item->userIdLinkField != "") {
        return 0;
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

    return 1;
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

function uploadFile($input, $fieldName, $subFolder) {
    $file = $input->files->get($fieldName);


    if (is_null($file) || $file['tmp_name'] == "") {
        error_log("File not found: " . $fieldName);
        error_log(var_export($input->files, true));
        error_log(var_export($file, true));
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
?>

<script>
    <?php if ($item->allowEdit || $item->allowCreate) { ?>

    function openNewForm() {
        $('#detailNavForm input[name="act"]').val('new');
        submitNavForm();
    }

    function openEditForm(recordId) {
        $('#detailNavForm input[name="act"]').val('edit');
        $('#detailNavForm input[name="recordId"]').val(recordId);
        submitNavForm();
    }

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

    function openDetailPage(recordId) {
        $('#detailNavForm input[name="act"]').val('detail');
        $('#detailNavForm input[name="recordId"]').val(recordId);
        submitNavForm();
    }

    function openListView() {
        $('#detailNavForm input[name="act"]').val('list');
        submitNavForm();
    }

    function goToPage(pageNumber) {
        $('#detailNavForm input[name="pageNumber"]').val(pageNumber);
        submitNavForm();
    }

    function doSearch() {
        $('#detailNavForm input[name="pageNumber"]').val(1);
        $('#detailNavForm input[name="searchTerm"]').val($('#searchForm input[name="searchTerm"]').val());
        submitNavForm();
    }

    function submitNavForm() {
        if ($('#detailNavForm input[name="act"]').val() == '') {
            $('#detailNavForm input[name="act"]').remove()
        }

        if ($('#detailNavForm input[name="recordId"]').val() == '') {
            $('#detailNavForm input[name="recordId"]').remove()
        }

        if ($('#detailNavForm input[name="searchTerm"]').val() == '') {
            $('#detailNavForm input[name="searchTerm"]').remove()
        }

        $('#detailNavForm').submit();
    }
</script>

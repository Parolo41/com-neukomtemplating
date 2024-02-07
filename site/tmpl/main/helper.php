<?php
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

function dbInsert($input, $db, $self) {
    $query = $db->getQuery(true);
    $item = $self->getModel()->getItem();

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

function dbUpdate($input, $db, $self) {
    $query = $db->getQuery(true);
    $item = $self->getModel()->getItem();

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

function dbDelete($input, $db, $self) {
    $query = $db->getQuery(true);
    $item = $self->getModel()->getItem();

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

<script>
    <?php if ($item->allowEdit || $item->allowCreate) { ?>

    function openEditForm(recordId) {
        document.getElementById("neukomtemplating-listview").style.display = "none";

        document.getElementById("recordId").value = recordId;
        document.getElementById("formAction").value = "update";

        fields.forEach((field) => {
            if (field[1] == "checkbox") {
                document.getElementById("neukomtemplating-input-" + field[0]).checked = (data[recordId][field[0]] == "1");
            } else if (field[1] == "image") {
                value = (data[recordId][field[0]] != "") ? data[recordId][field[0]] : "Kein Bild";
                document.getElementById("neukomtemplating-input-" + field[0] + "-current").innerHTML = value;
            } else {
                document.getElementById("neukomtemplating-input-" + field[0]).value = data[recordId][field[0]];
            }
        })

        joinedTables.forEach((joinedTable) => {
            if (Array.isArray(data[recordId][joinedTable[0]])) {
                multiselectDiv = document.getElementById("neukomtemplating-select-" + joinedTable[0]);

                for (i = 0; i < multiselectDiv.querySelectorAll('input').length; i++) {
                    multiselectOption = multiselectDiv.querySelectorAll('input')[i];
                    
                    multiselectOption.checked = data[recordId][joinedTable[0]].indexOf(multiselectOption.value) != -1;
                }
            } else {
                document.getElementById("neukomtemplating-select-" + joinedTable[0]).value = data[recordId][joinedTable[0]];
            }
        })

        document.getElementById("neukomtemplating-editform").style.display = "block";

        document.getElementById("neukomtemplating-formbuttons").style.display = "block";
        document.getElementById("neukomtemplating-deletebuttons").style.display = "none";
        document.getElementById("deleteRecordButton").style.display = "block";
    }

    function openNewForm() {
        document.getElementById("neukomtemplating-listview").style.display = "none";

        document.getElementById("recordId").value = "";
        document.getElementById("formAction").value = "insert";

        fields.forEach((field) => {
            if(field[1] == "checkbox") {
                document.getElementById("neukomtemplating-input-" + field[0]).checked = false;
            } else if (field[1] == "image") {
                document.getElementById("neukomtemplating-input-" + field[0] + "-current").innerHTML = "Kein Bild";
            } else {
                document.getElementById("neukomtemplating-input-" + field[0]).value = "";
            }
        })

        joinedTables.forEach((joinedTable) => {
            if (joinedTable[1] == "NToN") {
                multiselectDiv = document.getElementById("neukomtemplating-select-" + joinedTable[0]);

                for (i = 0; i < multiselectDiv.querySelectorAll('input').length; i++) {
                    multiselectOption = multiselectDiv.querySelectorAll('input')[i];
                    
                    multiselectOption.checked = false;
                }
            } else {
                document.getElementById("neukomtemplating-select-" + joinedTable[0]).value = '0';
            }
        })

        document.getElementById("neukomtemplating-editform").style.display = "block";

        document.getElementById("neukomtemplating-formbuttons").style.display = "block";
        document.getElementById("neukomtemplating-deletebuttons").style.display = "none";
        document.getElementById("deleteRecordButton").style.display = "none";
    }

    function openListView() {
        document.getElementById("neukomtemplating-editform").style.display = "none";

        document.getElementById("neukomtemplating-listview").style.display = "block";
    }

    function confirmDelete() {
        document.getElementById("formAction").value = "delete";

        document.getElementById("neukomtemplating-formbuttons").style.display = "none";
        document.getElementById("neukomtemplating-deletebuttons").style.display = "block";
    }

    function cancelDelete() {
        document.getElementById("formAction").value = "update";

        document.getElementById("neukomtemplating-formbuttons").style.display = "block";
        document.getElementById("neukomtemplating-deletebuttons").style.display = "none";
    }

    <?php } ?>
</script>

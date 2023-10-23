<?php

// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

$root = dirname(dirname(dirname(__FILE__)));
require_once($root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

$loader = new \Twig\Loader\ArrayLoader([
    'template' => $this->getModel()->getItem()->template,
]);
$twig = new \Twig\Environment($loader);

function validateInputFormat($value, $type) {
    $validationPatterns = array(
        'text' => "/.*/",
        'textarea' => "/(?s).*/",
        'date' => "/^\d{4}\-(0?[1-9]|1[012])\-(0?[1-9]|[12][0-9]|3[01])$/",
        'number' => "/^[0-9]*$/",
        'checkbox' => "/^(on)?$/",
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
        $fieldValue = $input->get($fieldName, '', 'string');

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
        $fieldValue = $input->get($fieldName, '', 'string');

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

if ($this->getModel()->getItem()->allowEdit && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = Factory::getDbo();
    $input = Factory::getApplication()->input;

    if ($input->get('formAction', '', 'string') == "insert") {
        dbInsert($input, $db, $this);
    }

    if ($input->get('formAction', '', 'string') == "update") {
        dbUpdate($input, $db, $this);
    }

    if ($input->get('formAction', '', 'string') == "delete") {
        dbDelete($input, $db, $this);
    }
}

$item = $this->getModel()->getItem();

?>

<script>
    const fields = [];

    <?php
        foreach ($item->fields as $field) {
            $fieldName = $field[0];
            $fieldType = $field[1];

            echo 'fields.push(["' . $fieldName . '", "' . $fieldType . '"]);';
        }
    ?>

    const joinedTables = [];

    <?php
        foreach ($item->joinedTables as $joinedTable) {
            if ($joinedTable->showInForm == false) {
                continue;
            }

            if ($joinedTable->connectionType == "OneToN") {
                continue;
            }

            $joinedTableName = $joinedTable->name;
            $joinedTableConnectionType = $joinedTable->connectionType;

            echo 'joinedTables.push(["' . $joinedTableName . '", "' . $joinedTableConnectionType . '"]);';
        }
    ?>

    const data = [];

    <?php
        foreach ($item->data as $data) {
            echo 'data[' . $data->id . '] = [];';

            foreach ($item->fields as $field) {
                $fieldName = $field[0];
                $fieldType = $field[1];

                echo 'data[' . $data->id . ']["' . $fieldName . '"] = "' . str_replace(["\""], "\\\"", str_replace(["\r\n", "\r", "\n", "\t"], "\\n", $data->{$fieldName})) . '";';
            }

            foreach ($item->joinedTables as $joinedTable) {
                $joinedTableName = $joinedTable->name;
                $joinedTableConnectionType = $joinedTable->connectionType;

                if ($joinedTableConnectionType == "NToOne") {
                    $joinedTableForeignId = (count($data->{$joinedTableName}) > 0 ? $data->{$joinedTableName}[0]->{$joinedTable->connectionInfo[1]} : "0");

                    echo 'data[' . $data->id . ']["' . $joinedTableName . '"] = "' . $joinedTableForeignId . '";';
                } else if ($joinedTableConnectionType == "NToN") {
                    echo 'data[' . $data->id . ']["' . $joinedTableName . '"] = [];';

                    foreach ($data->{$joinedTableName} as $joinedTableData) {
                        echo 'data[' . $data->id . ']["' . $joinedTableName . '"].push("' . $joinedTableData->{$joinedTable->connectionInfo[3]} . '");';
                    }
                }
            }
        }
    ?>

    <?php if ($item->allowEdit || $item->allowCreate) { ?>

    function openEditForm(recordId) {
        document.getElementById("neukomtemplating-listview").style.display = "none";

        document.getElementById("recordId").value = recordId;
        document.getElementById("formAction").value = "update";

        fields.forEach((field) => {
            if(field[1] == "checkbox") {
                document.getElementById("neukomtemplating-input-" + field[0]).checked = (data[recordId][field[0]] == "1");
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

<div id="neukomtemplating-listview">
    <?php
    echo $item->allowCreate ? '<button onClick="openNewForm()">Neu</button>' : "";
    echo $item->header;
    foreach ($item->data as $data) {
        echo $twig->render('template', ['data' => $data]);
        echo $item->allowEdit ? '<button onClick="openEditForm(' . $data->id . ')">Editieren</button>' : "";
    }
    echo $item->footer;
    ?>
</div>

<?php if ($item->allowEdit || $item->allowCreate) { ?>

<div id="neukomtemplating-editform" style="display: none">
    <form action="<?php echo Route::_(Uri::getInstance()->toString()); ?>" method="post" name="adminForm" id="adminForm" class="form-vertical">
        <?php
        foreach ($item->fields as $field) {
            $fieldName = $field[0];

            $permittedTypes = ["text", "textarea", "date", "number", "checkbox"];
            $fieldType = in_array($field[1], $permittedTypes) ? $field[1] : "text";

            $fieldDisplayName = $field[4];

            echo '<div id="neukomtemplating-field-' . $fieldName . '">';
            echo '<label for="neukomtemplating-input-' . $fieldName . '">' . $fieldDisplayName . '</label>';

            if ($fieldType == "textarea") {
                echo '<textarea id="neukomtemplating-input-' . $fieldName . '" name="' . $fieldName . '" class="neukomtemplating-textarea" rows="4" cols="50"></textarea><br>';
            } else {
                echo '<input type="' . $fieldType . '" id="neukomtemplating-input-' . $fieldName . '" name="' . $fieldName . '" class="neukomtemplating-' . $fieldType . '" /><br>';
            }

            echo '</div>';
        }

        foreach ($item->joinedTables as $joinedTable) {
            if ($joinedTable->showInForm == false) {
                continue;
            }

            if ($joinedTable->connectionType == "OneToN") {
                continue;
            }

            echo '<div id="neukomtemplating-joinedTable-' . $joinedTable->name . '">';
            echo '<label for="neukomtemplating-joinedTable-' . $joinedTable->name . '">' . $joinedTable->name . '</label>';

            if ($joinedTable->connectionType == "NToOne") {
                echo '<select id="neukomtemplating-select-' . $joinedTable->name . '" name="' . $joinedTable->name . '">';
                echo '<option value="0">Null</option>';

                foreach ($joinedTable->options as $option) {
                    echo '<option value="' . $option->{$joinedTable->connectionInfo[1]} . '">' . $option->{$joinedTable->displayField} . '</option>';
                }
                
                echo '</select><br>';
            } else if ($joinedTable->connectionType == "NToN") {
                echo '<div id="neukomtemplating-select-' . $joinedTable->name . '">';

                foreach ($joinedTable->options as $option) {
                    echo '<input type="checkbox" name="' . $joinedTable->name . '-' . $option->{$joinedTable->connectionInfo[3]} . '" value="' . $option->{$joinedTable->connectionInfo[3]} . '"></input>';
                    echo '<label>' . $option->{$joinedTable->displayField} . '</label><br>';
                }
                
                echo '</div><br>';
            }

            echo '</div>';
        }
        ?>

        <input type="hidden" id="formAction" name="formAction">
        <input type="hidden" id="recordId" name="recordId">

        <div id="neukomtemplating-formbuttons">
            <button type="submit">Eintragen</button>
            <button type="button" onClick="openListView()">Zurück</button>
            <button type="button" id="deleteRecordButton" onClick="confirmDelete()">Löschen</button>
        </div>

        <div id="neukomtemplating-deletebuttons">
            Bestätigen <br>
            <button type="submit">Ja</button>
            <button type="button" onClick="cancelDelete()">Nein</button>
        </div>
    </form>
</div>

<?php } ?>
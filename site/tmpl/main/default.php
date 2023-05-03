<?php

// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

$root = dirname(dirname(dirname(__FILE__)));
require_once($root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

$loader = new \Twig\Loader\ArrayLoader([
    'template' => $this->getModel()->getItem()->template,
]);
$twig = new \Twig\Environment($loader);

function validateInput($value, $type) {
    $validationPatterns = array(
        'text' => "/.*/",
        'textarea' => "/(?s).*/",
        'date' => "/^\d{4}\-(0?[1-9]|1[012])\-(0?[1-9]|[12][0-9]|3[01])$/",
        'number' => "/^[0-9]*$/",
        'checkbox' => "/^(on)?$/",
    );

    return preg_match($validationPatterns[$type], $value);
}

function dbInsert($input, $db, $self) {
    $query = $db->getQuery(true);

    $insertColumns = array();
    $insertValues = array();

    foreach ($self->getModel()->getItem()->fields as $field) {
        $fieldName = $field[0];
        $fieldType = $field[1];
        $fieldValue = $input->get($fieldName, '', 'string');

        $validationFailed = false;

        if (!validateInput($fieldValue, $fieldType)) {
            JFactory::getApplication()->enqueueMessage("Validation Error: $fieldValue does not match $fieldType", 'error');
            $validationFailed = true;
        }

        $insertColumns[] = $fieldName;
        $insertValues[] = $db->quote($fieldValue);
    }

    if ($validationFailed) {return 0;}

    $query
        ->insert('#__' . $self->getModel()->getItem()->templateName)
        ->columns($db->quoteName($insertColumns))
        ->values(implode(',', $insertValues));

    $db->setQuery($query);
    $db->execute();
}

function dbUpdate($input, $db, $self) {
    $query = $db->getQuery(true);

    $updateFields = array();

    foreach ($self->getModel()->getItem()->fields as $field) {
        $fieldName = $field[0];
        $fieldType = $field[1];
        $fieldValue = $input->get($fieldName, '', 'string');

        $validationFailed = false;

        if (!validateInput($fieldValue, $fieldType)) {
            JFactory::getApplication()->enqueueMessage("Validation Error: $fieldValue does not match $fieldType", 'error');
            $validationFailed = true;
        }

        $updateFields[] = $db->quoteName($fieldName) . " = " . $db->quote($fieldValue);
    }

    if ($validationFailed) {return 0;}

    $updateConditions = array(
        $db->quoteName('id') . ' = ' . $input->get('recordId', '', 'string')
    );

    $query
        ->update('#__' . $self->getModel()->getItem()->templateName)
        ->set($updateFields)
        ->where($updateConditions);

    $db->setQuery($query);

    $result = $db->execute();
}

function dbDelete($input, $db, $self) {
    $query = $db->getQuery(true);

    $deleteConditions = array($db->quoteName('id') . " = " . $input->get('recordId', '', 'string'));

    $query
        ->delete('#__' . $self->getModel()->getItem()->templateName)
        ->where($deleteConditions);

    $db->setQuery($query);

    $result = $db->execute();
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

    console.log(fields);

    const data = [];

    <?php
        foreach ($item->data as $data) {
            echo 'data[' . $data->id . '] = [];';

            foreach ($item->fields as $field) {
                $fieldName = $field[0];
                $fieldType = $field[1];

                echo 'data[' . $data->id . ']["' . $fieldName . '"] = "' . str_replace(["\r\n", "\r", "\n", "\t"], "\\n", $data->{$fieldName}) . '";';
            }
        }
    ?>

    console.log(data);

    <?php if ($this->getModel()->getItem()->allowEdit) { ?>

    function openEditForm(recordId) {
        document.getElementById("neukomtemplating-listview").style.display = "none";

        document.getElementById("recordId").value = recordId;
        document.getElementById("formAction").value = "update";

        fields.forEach((field) => {
            if(field[1] == "checkbox") {
                document.getElementById("neukomtemplating-input-" + field[0]).checked = (data[recordId][field[0]] == "on");
            } else {
                document.getElementById("neukomtemplating-input-" + field[0]).value = data[recordId][field[0]];
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
    echo $item->header;
    echo $this->getModel()->getItem()->allowEdit ? '<button onClick="openNewForm()">New</button>' : "";
    foreach ($item->data as $data) {
        echo $twig->render('template', ['data' => $data]);
        echo $this->getModel()->getItem()->allowEdit ? '<button onClick="openEditForm(' . $data->id . ')">Edit</button>' : "";
    }
    echo $item->footer;
    ?>
</div>

<?php if ($this->getModel()->getItem()->allowEdit) { ?>

<div id="neukomtemplating-editform" style="display: none">
    <form action="<?php echo Route::_('index.php?option=com_neukomtemplating&view=main&layout=default'); ?>" method="post" name="adminForm" id="adminForm" class="form-vertical">
        <?php
        foreach ($item->fields as $field) {
            $fieldName = $field[0];

            $permittedTypes = ["text", "textarea", "date", "number", "checkbox"];
            $fieldType = in_array($field[1], $permittedTypes) ? $field[1] : "text";

            echo '<div id="neukomtemplating-field-' . $fieldName . '>';
            echo '<label for="neukomtemplating-field-' . $fieldName . '">' . $fieldName . ':</label><br>';

            if ($fieldType == "textarea") {
                echo '<textarea id="neukomtemplating-input-' . $fieldName . '" name="' . $fieldName . '" rows="4" cols="50"></textarea><br>';
            } else {
                echo '<input type="' . $fieldType . '" id="neukomtemplating-input-' . $fieldName . '" name="' . $fieldName . '" /><br>';
            }

            echo '</div>';
        }
        ?>

        <input type="hidden" id="formAction" name="formAction">
        <input type="hidden" id="recordId" name="recordId">

        <div id="neukomtemplating-formbuttons">
            <button type="submit">Submit</button>
            <button type="button" onClick="openListView()">Back</button>
            <button type="button" id="deleteRecordButton" onClick="confirmDelete()">Delete</button>
        </div>

        <div id="neukomtemplating-deletebuttons">
            Confirm <br>
            <button type="submit">Yes</button>
            <button type="button" onClick="cancelDelete()">No</button>
        </div>
    </form>
</div>

<?php } ?>
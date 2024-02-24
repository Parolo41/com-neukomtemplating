<?php

// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\Filesystem\File;

$root = dirname(dirname(dirname(__FILE__)));
require_once($root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

$item = $this->getModel()->getItem();

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'helper.php');

$itemTemplate = $item->template;

if (strpos($itemTemplate, '{{editButton') == false) {
    $itemTemplate = $itemTemplate . '{{editButton | raw}}';
}

$loader = new \Twig\Loader\ArrayLoader([
    'template' => $itemTemplate,
    'detail_template' => $item->detailTemplate,
]);
$twig = new \Twig\Environment($loader);

if (($this->getModel()->getItem()->allowEdit || $this->getModel()->getItem()->allowCreate) && $_SERVER['REQUEST_METHOD'] === 'POST') {
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

            if (array_key_exists($fieldType, $item->aliases)) {
                continue;
            }

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
            $id = $data->{$item->idFieldName};
            echo 'data[' . $id . '] = [];';

            foreach ($item->fields as $field) {
                $fieldName = $field[0];
                $fieldType = $field[1];

                if (array_key_exists($fieldType, $item->aliases)) {
                    continue;
                }

                echo 'data[' . $id . ']["' . $fieldName . '"] = "' . str_replace(["\""], "\\\"", str_replace(["\r\n", "\r", "\n", "\t"], "\\n", $data->{$fieldName})) . '";';
            }

            foreach ($item->joinedTables as $joinedTable) {
                $joinedTableName = $joinedTable->name;
                $joinedTableConnectionType = $joinedTable->connectionType;

                if ($joinedTableConnectionType == "NToOne") {
                    $joinedTableForeignId = (count($data->{$joinedTableName}) > 0 ? $data->{$joinedTableName}[0]->{$joinedTable->connectionInfo[1]} : "0");

                    echo 'data[' . $id . ']["' . $joinedTableName . '"] = "' . $joinedTableForeignId . '";';
                } else if ($joinedTableConnectionType == "NToN") {
                    echo 'data[' . $id . ']["' . $joinedTableName . '"] = [];';

                    foreach ($data->{$joinedTableName} as $joinedTableData) {
                        echo 'data[' . $id . ']["' . $joinedTableName . '"].push("' . $joinedTableData->{$joinedTable->connectionInfo[3]} . '");';
                    }
                }
            }
        }
    ?>
</script>

<?php

$input = Factory::getApplication()->input;
$itemId = $input->get('itemId', 'none', 'string');

if ($itemId == 'none' || !$item->showDetailPage) {

?>

<?php if ($item->enableSearch) { ?>
    <div id="neukomtemplating-search">
        <form action="<?php echo Route::_(Uri::getInstance()->toString()); ?>" method="post" name="searchForm">
            <label for="searchTerm">Suche</label>
            <input type="text" name="searchTerm" value="<?php echo $input->get('searchTerm', '', 'string') ?>" />
            <button type="submit">Suchen</button>
        </form>
    </div>
<?php } ?>

<div id="neukomtemplating-listview">
    <?php
    echo $item->allowCreate ? '<button onClick="openNewForm()">Neu</button>' : "";
    echo $item->header;
    foreach ($item->data as $data) {
        $twigParams = [
            'data' => $data, 
            'editButton' => $item->allowEdit ? '<button onClick="openEditForm(' . $data->{$item->idFieldName} . ')">Editieren</button>' : "",
        ];
        echo $twig->render('template', array_merge($twigParams, $item->aliases));
    }
    echo $item->footer;
    ?>
</div>

<?php if ($item->allowEdit || $item->allowCreate) { ?>

<div id="neukomtemplating-editform" style="display: none">
    <form action="<?php echo Route::_(Uri::getInstance()->toString()); ?>" enctype="multipart/form-data" method="post" name="adminForm" id="adminForm" class="form-vertical">
        <?php
        $permittedTypes = ["text", "textarea", "date", "time", "number", "checkbox", "select", "image"];

        foreach ($item->fields as $field) {
            if (array_key_exists($field[1], $item->aliases)) {
                continue;
            }

            $fieldName = $field[0];
            $fieldType = in_array($field[1], $permittedTypes) ? $field[1] : "text";

            $fieldDisplayName = $field[4];

            echo '<div id="neukomtemplating-field-' . $fieldName . '">';
            echo '<label for="neukomtemplating-input-' . $fieldName . '">' . $fieldDisplayName . '</label>';

            if ($fieldType == "textarea") {
                echo '<textarea id="neukomtemplating-input-' . $fieldName . '" name="' . $fieldName . '" class="neukomtemplating-textarea" rows="4" cols="50"></textarea><br>';
            } else if ($fieldType == "select") {
                echo '<select id="neukomtemplating-input-' . $fieldName . '" name="' . $fieldName . '" class="neukomtemplating-select">';

                foreach (explode(',', $field[5]) as $selectOption) {
                    echo '<option value="' . $selectOption . '">' . $selectOption . '</option>';
                }

                echo '</select>';
            } else if ($fieldType == "image") {
                echo '<input type="file" accept="image/png, image/jpeg" id="neukomtemplating-input-' . $fieldName . '" name="' . $fieldName . '" class="neukomtemplating-image" /><br>';
                echo '<span id="neukomtemplating-input-' . $fieldName . '-current">Kein Bild</span><br>';
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
            <button type="button" id="backToListButton" onClick="openListView()">Zurück</button>
            <button type="button" id="deleteRecordButton" onClick="confirmDelete()">Löschen</button>
        </div>

        <div id="neukomtemplating-deletebuttons">
            Bestätigen <br>
            <button type="submit">Ja</button>
            <button type="button" onClick="cancelDelete()">Nein</button>
        </div>
    </form>
</div>

<?php
    if ($item->userIdLinkField != "" && $item->allowEdit) { 
        if (sizeof($item->data) == 0) {
            echo "<h2>No record found</h2>";
        } else {?>
            <script>
                openEditForm(<?php echo $item->data[0]->{$item->idFieldName}; ?>);
                document.getElementById("backToListButton").style.display = "none";
                document.getElementById("deleteRecordButton").style.display = "none";
            </script>
        <?php }
    }
}} else {
    foreach ($item->data as $data) {
        if ($data->{$item->idFieldName} == $itemId) {
            echo $twig->render('detail_template', ['data' => $data]);

            break;
        }
    }
} ?>
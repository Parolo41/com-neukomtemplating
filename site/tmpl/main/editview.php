<?php
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
?>

<div id="neukomtemplating-editform">
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

            $fieldValue = $data->{$fieldName};

            echo '<div id="neukomtemplating-field-' . $fieldName . '">';
            echo '<label for="neukomtemplating-input-' . $fieldName . '">' . $fieldDisplayName . '</label>';

            if ($fieldType == "textarea") {
                echo '<textarea id="neukomtemplating-input-' . $fieldName . '" name="' . $fieldName . '" class="neukomtemplating-textarea" rows="4" cols="50">' . $fieldValue . '</textarea><br>';
            } else if ($fieldType == "select") {
                echo '<select id="neukomtemplating-input-' . $fieldName . '" name="' . $fieldName . '" class="neukomtemplating-select">';

                foreach (explode(',', $field[5]) as $selectOption) {
                    $selected = $selectOption == $fieldValue ? ' selected ' : '';
                    echo '<option value="' . $selectOption . '"' . $selected . '>' . $selectOption . '</option>';
                }

                echo '</select>';
            } else if ($fieldType == "image") {
                echo '<input type="file" accept="image/png, image/jpeg" id="neukomtemplating-input-' . $fieldName . '" name="' . $fieldName . '" class="neukomtemplating-image" /><br>';
                echo '<span id="neukomtemplating-input-' . $fieldName . '-current">' . $fieldValue . '</span><br>';
            } else if ($fieldType == "checkbox") {
                $checked = $fieldValue == '1' ? ' checked ' : '';
                echo '<input type="checkbox" id="neukomtemplating-input-' . $fieldName . '" name="' . $fieldName . '"' . $checked . 'class="neukomtemplating-' . $fieldType . '" /><br>';
            } else {
                echo '<input type="' . $fieldType . '" id="neukomtemplating-input-' . $fieldName . '" name="' . $fieldName . '" value="' . $fieldValue . '" class="neukomtemplating-' . $fieldType . '" /><br>';
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
                    $selected = (sizeof($data->{$joinedTable->name}) > 0 && $option->{$joinedTable->connectionInfo[1]} == $data->{$joinedTable->name}[0]->{$joinedTable->connectionInfo[1]}) ? ' selected ' : '';
                    echo '<option value="' . $option->{$joinedTable->connectionInfo[1]} . '"' . $selected . '>' . $option->{$joinedTable->displayField} . '</option>';
                }
                
                echo '</select><br>';
            } else if ($joinedTable->connectionType == "NToN") {
                echo '<div id="neukomtemplating-select-' . $joinedTable->name . '">';

                $selectedIds = [];
                foreach ($data->{$joinedTable->name} as $selectedOption) {
                    $selectedIds[] = $selectedOption->{$joinedTable->connectionInfo[3]};
                }

                foreach ($joinedTable->options as $option) {
                    $checked = in_array($option->{$joinedTable->connectionInfo[3]}, $selectedIds) ? ' checked ' : '';
                    echo '<input type="checkbox" name="' . $joinedTable->name . '-' . $option->{$joinedTable->connectionInfo[3]} . '" value="' . $option->{$joinedTable->connectionInfo[3]} . '"' . $checked . '></input>';
                    echo '<label>' . $option->{$joinedTable->displayField} . '</label><br>';
                }
                
                echo '</div><br>';
            }

            echo '</div>';
        }
        ?>

        <input type="hidden" id="formAction" name="formAction" value="update">
        <input type="hidden" id="recordId" name="recordId" value="<?php echo $data->{$item->idFieldName} ?>">

        <div id="neukomtemplating-formbuttons">
            <button type="submit">Eintragen</button>
            <button type="button" id="backToListButton" onClick="openListView()">Zurück</button>
            <button type="button" id="deleteRecordButton" onClick="confirmDelete()">Löschen</button>
        </div>

        <div id="neukomtemplating-deletebuttons" style="display: none">
            Bestätigen <br>
            <button type="submit">Ja</button>
            <button type="button" onClick="cancelDelete()">Nein</button>
        </div>
    </form>
</div>
<?php
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
?>

<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>

<div id="neukomtemplating-editform">
    <form action="<?php echo Route::_(Uri::getInstance()->toString()); ?>" enctype="multipart/form-data" method="post" name="adminForm" id="adminForm" class="form-vertical">
        <?php
        $permittedTypes = ["text", "textarea", "texteditor", "date", "time", "number", "checkbox", "select", "image", "pdf"];

        foreach ($item->fields as $field) {
            if (array_key_exists($field['type'], $item->aliases)) {
                continue;
            }

            $fieldType = in_array($field['type'], $permittedTypes) ? $field['type'] : "text";

            $fieldValue = $data->{$field['name']};

            echo '<div id="neukomtemplating-field-' . $field['name'] . '">';
            echo '<label for="neukomtemplating-input-' . $field['name'] . '">' . $field['label'] . '</label>';

            if ($fieldType == "texteditor") {
                echo '<textarea id="neukomtemplating-input-' . $field['name'] . '" name="' . $field['name'] . '" class="neukomtemplating-textarea" rows="4" cols="50" hidden>' . $fieldValue . '</textarea><br>';
                
                ?>
                <div id="neukomtemplating-texteditor-<?php echo $field['name']; ?>">
                    <?php echo $fieldValue; ?>
                </div>

                <script>
                    try {
                        quills = ( typeof quills != 'undefined' && quills instanceof Array ) ? quills : []

                        quills['<?php echo $field['name']; ?>'] = new Quill('#neukomtemplating-texteditor-<?php echo $field['name']; ?>', {
                            theme: 'snow',
                        });

                        quills['<?php echo $field['name']; ?>'].on('text-change', (delta, oldDelta, source) => {
                            document.getElementById('neukomtemplating-input-<?php echo $field['name']; ?>').value = quills['<?php echo $field['name']; ?>'].getSemanticHTML();
                        });

                        document.getElementById('neukomtemplating-input-<?php echo $field['name']; ?>').style.display = 'none;'
                    } catch(e) {
                        document.getElementById('neukomtemplating-input-<?php echo $field['name']; ?>').style.display = 'block;'
                        document.getElementById('neukomtemplating-texteditor-<?php echo $field['name']; ?>').style.display = 'none;'
                    }
                </script>
                <?php
            } else if ($fieldType == "textarea") {
                echo '<textarea id="neukomtemplating-input-' . $field['name'] . '" name="' . $field['name'] . '" class="neukomtemplating-textarea" rows="4" cols="50">' . $fieldValue . '</textarea><br>'; 
            } else if ($fieldType == "select") {
                echo '<select id="neukomtemplating-input-' . $field['name'] . '" name="' . $field['name'] . '" class="neukomtemplating-select">';

                foreach (explode(',', $field['selectOptions']) as $selectOption) {
                    $selected = $selectOption == $fieldValue ? ' selected ' : '';
                    echo '<option value="' . $selectOption . '"' . $selected . '>' . $selectOption . '</option>';
                }

                echo '</select>';
            } else if ($fieldType == "image") {
                echo '<input type="file" accept="image/png, image/jpeg" id="neukomtemplating-input-' . $field['name'] . '" name="' . $field['name'] . '" class="neukomtemplating-image" /><br>';
                echo '<span id="neukomtemplating-input-' . $field['name'] . '-current">' . $fieldValue . '</span><br>';

                if (!$field['required']) {
                    echo '<input type="checkbox" id="neukomtemplating-input-' . $field['name'] . '-delete" name="' . $field['name'] . '-delete" />';
                    echo '<label for="neukomtemplating-input-' . $field['name'] . '-delete">' . Text::_('COM_NEUKOMTEMPLATING_DELETE') . '</label>';
                }
            } else if ($fieldType == "pdf") {
                echo '<input type="file" accept="application/pdf" id="neukomtemplating-input-' . $field['name'] . '" name="' . $field['name'] . '" class="neukomtemplating-image" /><br>';
                echo '<span id="neukomtemplating-input-' . $field['name'] . '-current">' . $fieldValue . '</span><br>';
                
                if (!$field['required']) {
                    echo '<input type="checkbox" id="neukomtemplating-input-' . $field['name'] . '-delete" name="' . $field['name'] . '-delete" />';
                    echo '<label for="neukomtemplating-input-' . $field['name'] . '-delete">' . Text::_('COM_NEUKOMTEMPLATING_DELETE') . '</label>';
                }
            } else if ($fieldType == "checkbox") {
                $checked = $fieldValue == '1' ? ' checked ' : '';
                echo '<input type="checkbox" id="neukomtemplating-input-' . $field['name'] . '" name="' . $field['name'] . '"' . $checked . 'class="neukomtemplating-' . $fieldType . '" /><br>';
            } else {
                echo '<input type="' . $fieldType . '" id="neukomtemplating-input-' . $field['name'] . '" name="' . $field['name'] . '" value="' . $fieldValue . '" class="neukomtemplating-' . $fieldType . '" /><br>';
            }

            echo '</div>';
        }

        foreach ($item->joinedTables as $joinedTable) {
            if (!$joinedTable['showInForm']) {
                continue;
            }

            if ($joinedTable['connectionType'] == "OneToN") {
                continue;
            }

            echo '<div id="neukomtemplating-joinedTable-' . $joinedTable['alias'] . '">';
            echo '<label for="neukomtemplating-joinedTable-' . $joinedTable['alias'] . '">' . $joinedTable['formName'] . '</label>';

            if ($joinedTable['connectionType'] == "NToOne") {
                echo '<select id="neukomtemplating-select-' . $joinedTable['alias'] . '" name="' . $joinedTable['alias'] . '">';
                echo '<option value="NULL">' . Text::_('COM_NEUKOMTEMPLATING_NONE') . '</option>';

                foreach ($joinedTable['options'] as $option) {
                    $selected = ($data->{$joinedTable['alias']} != null && $option->{$joinedTable['NToOne-remoteId']} == $data->{$joinedTable['alias']}[0]->{$joinedTable['NToOne-remoteId']}) ? ' selected ' : '';
                    echo '<option value="' . $option->{$joinedTable['NToOne-remoteId']} . '"' . $selected . '>' . $option->{$joinedTable['displayField']} . '</option>';
                }
                
                echo '</select><br>';
            } else if ($joinedTable['connectionType'] == "NToN") {
                echo '<div id="neukomtemplating-select-' . $joinedTable['alias'] . '">';

                $selectedIds = [];
                foreach ($data->{$joinedTable['alias']} as $selectedOption) {
                    $selectedIds[] = $selectedOption->{$joinedTable['NToN-remoteId']};
                }

                foreach ($joinedTable['options'] as $option) {
                    $checked = in_array($option->{$joinedTable['NToN-remoteId']}, $selectedIds) ? ' checked ' : '';
                    echo '<input type="checkbox" name="' . $joinedTable['alias'] . '-' . $option->{$joinedTable['NToN-remoteId']} . '" value="' . $option->{$joinedTable['NToN-remoteId']} . '"' . $checked . '></input>';
                    echo '<label>' . $option->{$joinedTable['displayField']} . '</label><br>';
                }
                
                echo '</div><br>';
            }

            echo '</div>';
        }
        ?>

        <input type="hidden" id="formAction" name="formAction" value="update">
        <input type="hidden" id="recordId" name="recordId" value="<?php echo $data->{$item->idFieldName} ?>">

        <div id="neukomtemplating-formbuttons">
            <button type="submit"><?php echo Text::_('COM_NEUKOMTEMPLATING_SUBMIT') ?></button>
            <button type="button" id="backToListButton" onClick="openListView()"><?php echo Text::_('COM_NEUKOMTEMPLATING_BACK') ?></button>
            <button type="button" id="deleteRecordButton" onClick="confirmDelete()"><?php echo Text::_('COM_NEUKOMTEMPLATING_DELETE') ?></button>
        </div>

        <div id="neukomtemplating-deletebuttons" style="display: none">
            <?php echo Text::_('COM_NEUKOMTEMPLATING_CONFIRM'); ?> <br>
            <button type="submit"><?php echo Text::_('COM_NEUKOMTEMPLATING_YES'); ?></button>
            <button type="button" onClick="cancelDelete()"><?php echo Text::_('COM_NEUKOMTEMPLATING_NO'); ?></button>
        </div>
    </form>
</div>
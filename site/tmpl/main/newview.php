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

            echo '<div id="neukomtemplating-field-' . $field['name'] . '">';
            echo '<label for="neukomtemplating-input-' . $field['name'] . '">' . $field['label'] . '</label>';

            if ($fieldType == "texteditor") {
                echo '<textarea id="neukomtemplating-input-' . $field['name'] . '" name="' . $field['name'] . '" class="neukomtemplating-textarea" rows="4" cols="50" hidden></textarea><br>';
                
                ?>
                <div id="neukomtemplating-texteditor-<?php echo $field['name']; ?>"></div>

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
                echo '<textarea id="neukomtemplating-input-' . $field['name'] . '" name="' . $field['name'] . '" class="neukomtemplating-textarea" rows="4" cols="50"></textarea><br>';
            } else if ($fieldType == "select") {
                echo '<select id="neukomtemplating-input-' . $field['name'] . '" name="' . $field['name'] . '" class="neukomtemplating-select">';

                foreach (explode(',', $field['selectOptions']) as $selectOption) {
                    echo '<option value="' . $selectOption . '">' . $selectOption . '</option>';
                }

                echo '</select>';
            } else if ($fieldType == "image") {
                echo '<input type="file" accept="image/png, image/jpeg" id="neukomtemplating-input-' . $field['name'] . '" name="' . $field['name'] . '" class="neukomtemplating-image" /><br>';
                echo '<span id="neukomtemplating-input-' . $field['name'] . '-current">Kein Bild</span><br>';
            } else if ($fieldType == "pdf") {
                echo '<input type="file" accept="application/pdf" id="neukomtemplating-input-' . $field['name'] . '" name="' . $field['name'] . '" class="neukomtemplating-image" /><br>';
                echo '<span id="neukomtemplating-input-' . $field['name'] . '-current">Kein PDF</span><br>';
            } else {
                echo '<input type="' . $fieldType . '" id="neukomtemplating-input-' . $field['name'] . '" name="' . $field['name'] . '" class="neukomtemplating-' . $fieldType . '" /><br>';
            }

            echo '</div>';
        }

        foreach ($item->joinedTables as $joinedTable) {
            if ($joinedTable['showInForm'] == false) {
                continue;
            }

            if ($joinedTable['connectionType'] == "OneToN") {
                continue;
            }

            echo '<div id="neukomtemplating-joinedTable-' . $joinedTable['alias'] . '">';
            echo '<label for="neukomtemplating-joinedTable-' . $joinedTable['alias'] . '">' . $joinedTable['formName'] . '</label>';

            if ($joinedTable['connectionType'] == "NToOne") {
                echo '<select id="neukomtemplating-select-' . $joinedTable['alias'] . '" name="' . $joinedTable['alias'] . '">';
                echo '<option value="NULL">Null</option>';

                foreach ($joinedTable['options'] as $option) {
                    echo '<option value="' . $option->{$joinedTable['NToN-intermediateLocalKey']} . '">' . $option->{$joinedTable['displayField']} . '</option>';
                }
                
                echo '</select><br>';
            } else if ($joinedTable['connectionType'] == "NToN") {
                echo '<div id="neukomtemplating-select-' . $joinedTable['alias'] . '">';

                foreach ($joinedTable['options'] as $option) {
                    echo '<input type="checkbox" name="' . $joinedTable['alias'] . '-' . $option->{$joinedTable['NToN-remoteId']} . '" value="' . $option->{$joinedTable['NToN-remoteId']} . '"></input>';
                    echo '<label>' . $option->{$joinedTable['displayField']} . '</label><br>';
                }
                
                echo '</div><br>';
            }

            echo '</div>';
        }
        ?>

        <input type="hidden" id="formAction" name="formAction" value="insert">

        <div id="neukomtemplating-formbuttons">
            <button type="submit" class="btn btn-primary"><?php echo Text::_('COM_NEUKOMTEMPLATING_SUBMIT'); ?></button>
            <a type="button" class="btn btn-primary" id="backToListButton" href="<?php echo buildUrl($this, 'list'); ?>"><?php echo Text::_('COM_NEUKOMTEMPLATING_BACK'); ?></a>
        </div>
    </form>
</div>
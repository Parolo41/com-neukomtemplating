<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$app = Factory::getApplication();
$input = $app->input;

$wa = $this->document->getWebAssetManager();
$wa->getRegistry()->addExtensionRegistryFile('com_contenthistory');
$wa->useScript('keepalive')
    ->useScript('form.validate');

$layout  = 'edit';
$tmpl = $input->get('tmpl', '', 'cmd') === 'component' ? '&tmpl=component' : '';
?>

<style>
    .field-info-label {display: inline-block;width: 120px;}
    .joined-table-info-label {display: inline-block;width: 200px;}
    .joined-table-foreign-fields-input {width: 500px;}
</style>

<form action="<?php echo Route::_('index.php?option=com_neukomtemplating&layout=' . $layout . $tmpl . '&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="template-form" class="form-validate">
    <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', []); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'details', 'Details'); ?>
    <?php echo $this->getForm()->renderField('name'); ?>
    <?php echo $this->getForm()->renderField('tablename'); ?>
    <?php echo $this->getForm()->renderField('id_field_name'); ?>
    <?php echo $this->getForm()->renderField('spacer'); ?>

    <?php echo $this->getForm()->renderField('fields_spacer'); ?>
    <div id="template-fields-area"></div>
    <button type="button" onclick="addField()"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_ADD_FIELD'); ?></button> <br/>
    <?php echo $this->getForm()->renderField('spacer'); ?>

    <?php echo $this->getForm()->renderField('parameters_spacer'); ?>
    <div id="url-parameters-area"></div>
    <button type="button" onclick="addUrlParameter()"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_ADD_PARAMETER'); ?></button> <br/>
    <?php echo $this->getForm()->renderField('spacer'); ?>
    
    <?php echo $this->getForm()->renderField('condition'); ?>
    <div class="control-group">
        <div class="control-label"></div>
        <div class="controls"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_CONDITION_TOOLTIP'); ?></div>
    </div>
    <?php echo $this->getForm()->renderField('sorting'); ?>
    <?php echo $this->getForm()->renderField('limit'); ?>
    <?php echo $this->getForm()->renderField('user_id_link_field'); ?>
    <div class="control-group">
        <div class="control-label"></div>
        <div class="controls"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_USER_ID_LINK_FIELD_TOOLTIP'); ?></div>
    </div>
    <?php echo $this->getForm()->renderField('header'); ?>
    <?php echo $this->getForm()->renderField('template'); ?>
    <div class="control-group">
        <div class="control-label"></div>
        <div class="controls"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_TEMPLATE_TOOLTIP'); ?></div>
    </div>
    <?php echo $this->getForm()->renderField('footer'); ?>
    <?php echo $this->getForm()->renderField('detail_template'); ?>
    <?php echo $this->getForm()->renderField('show_detail_page'); ?>
    <div class="control-group">
        <div class="control-label"></div>
        <div class="controls"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_SHOW_DETAIL_PAGE_TOOLTIP'); ?></div>
    </div>
    <?php echo $this->getForm()->renderField('enable_search'); ?>
    <?php echo $this->getForm()->renderField('enable_pagination'); ?>
    <?php echo $this->getForm()->renderField('page_size'); ?>
    <?php echo $this->getForm()->renderField('allow_edit'); ?>
    <?php echo $this->getForm()->renderField('allow_create'); ?>
    <?php echo $this->getForm()->renderField('form_send_behaviour'); ?>
    <?php echo $this->getForm()->renderField('access'); ?>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'joined_tables_tab', 'Joined Tables'); ?>
    <?php echo $this->getForm()->renderField('joined_spacer'); ?>
    <div id="joined-tables-area"></div>
    <button type="button" onclick="addJoinedTable()"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_ADD_JOINED_TABLE'); ?></button>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>

<form name="jsonPreloads" hidden>
    <?php echo $this->getForm()->renderField('fields'); ?>
    <?php echo $this->getForm()->renderField('url_parameters'); ?>
    <?php echo $this->getForm()->renderField('joined_tables'); ?>
</form>

<div id="template-field-blueprint" style="margin-bottom: 16px" hidden>
    <span class="field-info-label" name="name-label"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_FIELD_NAME'); ?></span>
    <input type="text" name="field_name[__ID__]" class="field_input field_name" /> <br/>

    <span class="field-info-label" name="showInForm-label"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_FIELD_SHOW_IN_FORM'); ?></span>
    <select name="field_showInForm[__ID__]" class="field_input field_showInForm" onchange="updateFieldInputVisibility()">
        <option value="0"><?php echo Text::_('COM_NEUKOMTEMPLATING_NO'); ?></option>
        <option value="1"><?php echo Text::_('COM_NEUKOMTEMPLATING_YES'); ?></option>
    </select><br/>

    <div name="show-on-showInForm" hidden>
        <span class="field-info-label" name="displayName-label"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_FIELD_LABEL'); ?></span>
        <input type="text" name="field_label[__ID__]" class="field_input field_label" /> <br/>

        <span class="field-info-label" name="type-label"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_FIELD_TYPE'); ?></span>
        <select name="field_type[__ID__]" class="field_input field_type" onchange="updateFieldInputVisibility()">
            <option value="text"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_FIELD_TYPE_TEXT'); ?></option>
            <option value="textarea"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_FIELD_TYPE_TEXTAREA'); ?></option>
            <option value="texteditor"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_FIELD_TYPE_TEXTEDITOR'); ?></option>
            <option value="number"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_FIELD_TYPE_NUMBER'); ?></option>
            <option value="date"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_FIELD_TYPE_DATE'); ?></option>
            <option value="time"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_FIELD_TYPE_TIME'); ?></option>
            <option value="checkbox"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_FIELD_TYPE_CHECKBOX'); ?></option>
            <option value="select"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_FIELD_TYPE_SELECT'); ?></option>
            <option value="image"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_FIELD_TYPE_IMAGE'); ?></option>
            <option value="pdf"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_FIELD_TYPE_PDF'); ?></option>
            <option value="userid"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_FIELD_TYPE_USERID'); ?></option>
            <option value="username"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_FIELD_TYPE_USERNAME'); ?></option>
        </select> <br/>

        <div name="show-on-typeSelect">
            <span class="field-info-label" name="type-label"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_FIELD_SELECT_OPTIONS'); ?></span>
            <input type="text" name="field_selectOptions[__ID__]" class="field_input field_selectOptions" /> <br/>
        </div>

        <span class="field-info-label" name="required-label"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_FIELD_REQUIRED'); ?></span>
        <select name="field_required[__ID__]" class="field_input field_required" onchange="updateFieldInputVisibility()">
            <option value="0"><?php echo Text::_('COM_NEUKOMTEMPLATING_NO'); ?></option>
            <option value="1"><?php echo Text::_('COM_NEUKOMTEMPLATING_YES'); ?></option>
        </select><br/>
    </div>

    <button type="button" onclick="removeField(this)"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_REMOVE_FIELD'); ?></button>
    <button type="button" onclick="moveUp(this)"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_UP'); ?></button>
    <button type="button" onclick="moveDown(this)"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_DOWN'); ?></button>
</div>

<div id="url-parameter-blueprint" style="margin-bottom: 16px" hidden>
    <span class="field-info-label" name="name-label">
        <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_URL_PARAMETER_NAME'); ?> </span>
    <input type="text" name="parameter_name[__ID__]" class="parameter_input parameter_name" /> <br/>
    
    <span class="field-info-label" name="default-label">
        <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_URL_PARAMETER_DEFAULT'); ?></span>
    <input type="text" name="parameter_default[__ID__]" class="parameter_input parameter_default" /> <br/>

    <span class="field-info-label" name="insertIntoDb-label">
        <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_URL_PARAMETER_DB_INSERT'); ?></span>
    <select name="parameter_insertIntoDb[__ID__]" class="parameter_input parameter_insertIntoDb">
        <option value="0"><?php echo Text::_('COM_NEUKOMTEMPLATING_NO'); ?></option>
        <option value="1"><?php echo Text::_('COM_NEUKOMTEMPLATING_YES'); ?></option>
    </select><br/>
    
    <button type="button" onclick="removeParameter(this)"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_REMOVE_PARAMETER'); ?></button>
</div>

<div id="joined-table-blueprint" style="margin-bottom: 16px" hidden>
    <span class="joined-table-info-label" name="name-label" title="DB name of the joined table">
        <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_JOINED_TABLE_NAME'); ?></span>
    <input type="text" name="joined_name[__ID__]" class="joined_input joined_name" /> <br/>
    
    <span class="joined-table-info-label" name="displayField-label" title="DB name of the field containing the display name of joined table records">
        <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_JOINED_TABLE_LABEL'); ?></span>
    <input type="text" name="joined_displayField[__ID__]" class="joined_input joined_displayField" /> <br/>

    <span class="joined-table-info-label" name="type-label" title="Connection type between local and joined table">
        <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_JOINED_TABLE_CONNECTION_TYPE'); ?></span>
    <select name="joined_connectionType[__ID__]" class="joined_input joined_connectionType" onchange="updateJoinedTableInputVisibility()">
        <option value="NToOne">n:1</option>
        <option value="OneToN">1:n</option>
        <option value="NToN">n:n</option>
    </select> <br/>
    
    <div name="show-on-NToOne">
        <span class="joined-table-info-label" name="NToOne-foreignKey-label" title="DB name of the foreign key field at the local table, connecting to the joined table">
            <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_JOINED_TABLE_LOCAL_FOREIGN_KEY'); ?></span>
        <input type="text" name="joined_NToOne-foreignKey[__ID__]" class="joined_input joined_NToOne-foreignKey" /> <br/>

        <span class="joined-table-info-label" name="NToOne-remoteId-label" title="DB name of the ID field at the joined table">
            <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_JOINED_TABLE_REMOTE_ID'); ?></span>
        <input type="text" name="joined_NToOne-remoteId[__ID__]" class="joined_input joined_NToOne-remoteId" /> <br/>
    </div>

    <div name="show-on-OneToN" hidden>
        <span class="joined-table-info-label" name="OneToN-foreignKey-label" title="DB name of the foreign key field at the joined table, connecting to the main table">
            <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_JOINED_TABLE_REMOTE_FOREIGN_KEY'); ?></span>
        <input type="text" name="joined_OneToN-foreignKey[__ID__]" class="joined_input joined_OneToN-foreignKey" /> <br/>
    </div>
    
    <div name="show-on-NToN" hidden>
        <span class="joined-table-info-label" name="NToN-intermediateTable-label" title="DB name of the connecting table between the local and joined table">
            <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_JOINED_TABLE_INTERMEDIATE_NAME'); ?></span>
        <input type="text" name="joined_NToN-intermediateTable[__ID__]" class="joined_input joined_NToN-intermediateTable" /> <br/>

        <span class="joined-table-info-label" name="NToN-intermediateLocalKey-label" title="DB name of the foreign key field at the connecting table, connecting to the local table">
            <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_JOINED_TABLE_INTERMEDIATE_LOCAL_KEY'); ?></span>
        <input type="text" name="joined_NToN-intermediateLocalKey[__ID__]" class="joined_input joined_NToN-intermediateLocalKey" /> <br/>

        <span class="joined-table-info-label" name="NToN-intermediateRemoteKey-label" title="DB name of the foreign key field at the connecting table, connecting to the joined table">
            <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_JOINED_TABLE_INTERMEDIATE_REMOTE_KEY'); ?></span>
        <input type="text" name="joined_NToN-intermediateRemoteKey[__ID__]" class="joined_input joined_NToN-intermediateRemoteKey" /> <br/>

        <span class="joined-table-info-label" name="NToN-remoteId-label" title="DB name of the ID field at the joined table">
            <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_JOINED_TABLE_REMOTE_ID'); ?></span>
        <input type="text" name="joined_NToN-remoteId[__ID__]" class="joined_input joined_NToN-remoteId" /> <br/>
    </div>
    
    <span class="joined-table-info-label" name="foreignFields-label" title="Fields to load from joined table">
        <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_JOINED_TABLE_FIELDS'); ?></span>
    <input class="joined-table-foreign-fields-input joined_input joined_foreignFields" type="text" name="joined_foreignFields[__ID__]" /> <br/>
    
    <span class="joined-table-info-label" name="alias-label" title="What the joined table is called in the template">
        <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_JOINED_TABLE_ALIAS'); ?></span>
    <input class="joined-table-alias-input joined_input joined_alias" type="text" name="joined_alias[__ID__]" /> <br/>

    <span class="joined-table-info-label" name="showInForm-label" title="Display joined table options in edit form">
        <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_JOINED_TABLE_SHOW_IN_FORM'); ?></span>
    <select name="joined_showInForm[__ID__]" class="joined_input joined_showInForm">
        <option value="0"><?php echo Text::_('COM_NEUKOMTEMPLATING_NO'); ?></option>
        <option value="1"><?php echo Text::_('COM_NEUKOMTEMPLATING_YES'); ?></option>
    </select><br/>
    
    <span class="joined-table-info-label" name="formName-label" title="What the joined table is called in the form">
        <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_JOINED_TABLE_FORM_NAME'); ?></span>
    <input class="joined-table-form-name-input joined_input joined_formName" type="text" name="joined_formName[__ID__]" /> <br/>

    <button type="button" onclick="removeJoinedTable(this)"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_REMOVE_JOINED_TABLE'); ?></button>
</div>

<script>
    fieldNumber = 0;
    parameterNumber = 0;
    joinedTableNumber = 0;

    function addField() {
        const clone = document.getElementById("template-field-blueprint").cloneNode(true);
        clone.id = "template-field-" + fieldNumber;
        clone.hidden = false;

        for (const element of clone.querySelectorAll('.field_input')) {
            element.name = element.name.replace("__ID__", String(fieldNumber));
        }

        document.getElementById("template-fields-area").appendChild(clone);
        
        fieldNumber += 1;
    }

    function removeField(button) {
        if (confirm('<?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_REMOVE_FIELD_CONFIRM'); ?>')) {
            button.parentElement.remove();
        }
    }

    function moveUp(button) {
        fieldElement = button.parentElement;

        if (fieldElement.previousElementSibling)
            fieldElement.parentNode.insertBefore(fieldElement, fieldElement.previousElementSibling);
    }

    function moveDown(button) {
        fieldElement = button.parentElement;

        if (fieldElement.nextElementSibling)
            fieldElement.parentNode.insertBefore(fieldElement.nextElementSibling, fieldElement);
    }

    function addUrlParameter() {
        const clone = document.getElementById("url-parameter-blueprint").cloneNode(true);
        clone.id = "url-parameter-" + parameterNumber;
        clone.hidden = false;

        for (const element of clone.querySelectorAll('.parameter_input')) {
            element.name = element.name.replace("__ID__", String(parameterNumber));
        }

        document.getElementById("url-parameters-area").appendChild(clone);
        
        parameterNumber += 1;
    }

    function removeParameter(button) {
        if (confirm('<?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_REMOVE_PARAMETER_CONFIRM'); ?>')) {
            button.parentElement.remove();
        }
    }

    function addJoinedTable() {
        const clone = document.getElementById("joined-table-blueprint").cloneNode(true);
        clone.id = "joined-table-" + joinedTableNumber;
        clone.hidden = false;

        for (const element of clone.querySelectorAll('.joined_input')) {
            element.name = element.name.replace("__ID__", String(joinedTableNumber));
        }

        document.getElementById("joined-tables-area").appendChild(clone);
        
        joinedTableNumber += 1;
    }

    function removeJoinedTable(button) {
        if (confirm('<?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_REMOVE_JOINED_FIELD_CONFIRM'); ?>')) {
            button.parentElement.remove();
        }
    }

    function updateFieldInputVisibility() {
        templateFieldsArea = document.getElementById("template-fields-area");

        for (let i = 0; i < templateFieldsArea.childNodes.length; i++) {
            field = templateFieldsArea.childNodes[i];

            showInForm = field.querySelector('.field_showInForm').value == "1";

            field.querySelector('div[name="show-on-showInForm"]').hidden = !showInForm;

            inputType = field.querySelector('.field_type').value;

            field.querySelector('div[name="show-on-typeSelect"]').hidden = !(inputType == "select");
        }
    }

    function updateJoinedTableInputVisibility() {
        joinedTablesArea = document.getElementById("joined-tables-area");

        for (let i = 0; i < joinedTablesArea.childNodes.length; i++) {
            joinedTable = joinedTablesArea.childNodes[i];

            joinedTable.querySelector('div[name="show-on-NToOne"]').hidden = true;
            joinedTable.querySelector('div[name="show-on-OneToN"]').hidden = true;
            joinedTable.querySelector('div[name="show-on-NToN"]').hidden = true;

            visibleInput = joinedTable.querySelector('.joined_connectionType').value;

            joinedTable.querySelector('div[name="show-on-' + visibleInput + '"]').hidden = false;
        }
    }

    loadedFields = (document.jsonPreloads.jform_fields.value != "" ? JSON.parse(document.jsonPreloads.jform_fields.value) : []);
    loadedParameters = (document.jsonPreloads.jform_url_parameters.value != "" ? JSON.parse(document.jsonPreloads.jform_url_parameters.value) : []);
    loadedJoinedTables = (document.jsonPreloads.jform_joined_tables.value != "" ? JSON.parse(document.jsonPreloads.jform_joined_tables.value) : []);

    for (let i = 0; i < loadedFields.length; i++) {
        try {
            fieldValues = loadedFields[i];

            addField();
            newField = document.getElementById("template-fields-area").lastChild;

            for (const [key, value] of Object.entries(fieldValues)) {
                fieldInput = newField.querySelector('.field_' + key);

                if (fieldInput != null) {
                    fieldInput.value = value;
                }
            }
        } catch (error) {
            console.error(error);
        }
    }

    for (let i = 0; i < loadedParameters.length; i++) {
        try {
            parameterValues = loadedParameters[i];

            addUrlParameter();
            newParameter = document.getElementById("url-parameters-area").lastChild;

            for (const [key, value] of Object.entries(parameterValues)) {
                parameterInput = newParameter.querySelector('.parameter_' + key);

                if (parameterInput != null) {
                    parameterInput.value = value;
                }
            }
        } catch (error) {
            console.error(error);
        }
    }

    for (let i = 0; i < loadedJoinedTables.length; i++) {
        try {
            joinedTableValues = loadedJoinedTables[i];

            addJoinedTable();
            newJoinedTable = document.getElementById("joined-tables-area").lastChild;

            for (const [key, value] of Object.entries(joinedTableValues)) {
                joinedInput = newJoinedTable.querySelector('.joined_' + key);

                if (joinedInput != null) {
                    joinedInput.value = value;
                }
            }
        } catch (error) {
            console.error(error);
        }
    }

    updateFieldInputVisibility();
    updateJoinedTableInputVisibility();
</script>
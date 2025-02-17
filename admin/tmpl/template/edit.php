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
    .joined-table-info-label.bold {font-weight: bold;}
    hr.solid {border-top: 3px solid #bbb;}
</style>

<form action="<?php echo Route::_('index.php?option=com_neukomtemplating&layout=' . $layout . $tmpl . '&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="template-form" class="form-validate" hidden>
    <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', []); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'details', 'Details'); ?>
    <?php echo $this->getForm()->renderField('name'); ?>
    <?php echo $this->getForm()->renderField('tablename'); ?>
    <?php echo $this->getForm()->renderField('id_field_name'); ?>
    <?php echo $this->getForm()->renderField('fields'); ?>
    <?php echo $this->getForm()->renderField('url_parameters'); ?>
    <?php echo $this->getForm()->renderField('condition'); ?>
    <?php echo $this->getForm()->renderField('sorting'); ?>
    <?php echo $this->getForm()->renderField('limit'); ?>
    <?php echo $this->getForm()->renderField('user_id_link_field'); ?>
    <?php echo $this->getForm()->renderField('header'); ?>
    <?php echo $this->getForm()->renderField('template'); ?>
    <?php echo $this->getForm()->renderField('footer'); ?>
    <?php echo $this->getForm()->renderField('detail_template'); ?>
    <?php echo $this->getForm()->renderField('show_detail_page'); ?>
    <?php echo $this->getForm()->renderField('enable_search'); ?>
    <?php echo $this->getForm()->renderField('enable_pagination'); ?>
    <?php echo $this->getForm()->renderField('page_size'); ?>
    <?php echo $this->getForm()->renderField('allow_edit'); ?>
    <?php echo $this->getForm()->renderField('allow_create'); ?>
    <?php echo $this->getForm()->renderField('form_send_behaviour'); ?>
    <?php echo $this->getForm()->renderField('access'); ?>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'joined_tables_tab', 'Joined Tables'); ?>
    <?php echo $this->getForm()->renderField('joined_tables'); ?>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.endTabSet'); ?>

    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>

<form id="dummyForm" name="dummyForm" onchange="updateValues()">
    <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', []); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'details', 'Details'); ?>
    <?php echo $this->getForm()->renderField('name'); ?>
    <?php echo $this->getForm()->renderField('tablename'); ?>
    <?php echo $this->getForm()->renderField('id_field_name'); ?>
    <label>Fields</label>
    <div id="template-fields-area"></div>
    <button type="button" onclick="addField()"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_ADD_FIELD'); ?></button> <br/>
    <label><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_URL_PARAMETERS'); ?></label>
    <div id="url-parameters-area"></div>
    <button type="button" onclick="addUrlParameter()"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_ADD_PARAMETER'); ?></button> <br/>
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
    <label><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_JOINED_TABLES'); ?></label>
    <div id="joined-tables-area"></div>
    <button type="button" onclick="addJoinedTable()"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_ADD_JOINED_TABLE'); ?></button>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>

<div id="template-field-blueprint" style="margin-bottom: 16px" hidden>
    <span class="field-info-label" name="name-label"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_FIELD_NAME'); ?></span>
    <input type="text" name="name" /> <br/>

    <span class="field-info-label" name="showInForm-label"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_FIELD_SHOW_IN_FORM'); ?></span>
    <input type="checkbox" name="showInForm" onchange="updateFieldInputVisibility()" checked /> <br/>

    <div name="show-on-showInForm">
        <span class="field-info-label" name="displayName-label"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_FIELD_LABEL'); ?></span>
        <input type="text" name="displayName" /> <br/>

        <span class="field-info-label" name="type-label"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_FIELD_TYPE'); ?></span>
        <select name="type" onchange="updateFieldInputVisibility()">
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

        <div name="show-on-typeSelect" hidden>
            <span class="field-info-label" name="type-label"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_FIELD_SELECT_OPTIONS'); ?></span>
            <input type="text" name="selectOptions" /> <br/>
        </div>

        <span class="field-info-label" name="required-label"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_FIELD_REQUIRED'); ?></span>
        <input type="checkbox" name="required" /> <br/>
    </div>
</div>

<div id="url-parameter-blueprint" style="margin-bottom: 16px" hidden>
    <span class="field-info-label" name="name-label">
        <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_URL_PARAMETER_NAME'); ?> </span>
    <input type="text" name="name" /> <br/>
    
    <span class="field-info-label" name="default-label">
        <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_URL_PARAMETER_DEFAULT'); ?></span>
    <input type="text" name="default" /> <br/>

    <span class="field-info-label" name="insertIntoDb-label">
        <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_URL_PARAMETER_DB_INSERT'); ?></span>
    <input type="checkbox" name="insertIntoDb" /> <br/>
</div>

<div id="joined-table-blueprint" style="margin-bottom: 16px" hidden>
    <span class="joined-table-info-label" name="name-label" title="DB name of the joined table">
        <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_JOINED_TABLE_NAME'); ?></span>
    <input type="text" name="name" /> <br/>
    
    <span class="joined-table-info-label" name="alias-label" title="What the joined table is called in the template">
        <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_JOINED_TABLE_ALIAS'); ?></span>
    <input class="joined-table-alias-input" type="text" name="alias" /> <br/>
    
    <span class="joined-table-info-label" name="formName-label" title="What the joined table is called in the form">
        <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_JOINED_TABLE_FORM_NAME'); ?></span>
    <input class="joined-table-form-name-input" type="text" name="formName" /> <br/>
    
    <span class="joined-table-info-label" name="displayField-label" title="DB name of the field containing the display name of joined table records">
        <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_JOINED_TABLE_LABEL'); ?></span>
    <input type="text" name="displayField" /> <br/>
    
    <span class="joined-table-info-label" name="foreignFields-label" title="Fields to load from joined table">
        <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_JOINED_TABLE_FIELDS'); ?></span>
    <input class="joined-table-foreign-fields-input" type="text" name="foreignFields" /> <br/>

    <span class="joined-table-info-label" name="showInForm-label" title="Display joined table options in edit form">
        <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_JOINED_TABLE_SHOW_IN_FORM'); ?></span>
    <input type="checkbox" name="showInForm" /> <br/>

    <span class="joined-table-info-label" name="type-label" title="Connection type between local and joined table">
        <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_JOINED_TABLE_CONNECTION_TYPE'); ?></span>
    <select name="type" onchange="updateJoinedTableInputVisibility()">
        <option value="NToOne">n:1</option>
        <option value="OneToN">1:n</option>
        <option value="NToN">n:n</option>
    </select> <br/>

    <span class="joined-table-info-label bold" title="DB name of the foreign key field at the local table, connecting to the joined table">
            <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_JOINED_TABLE_CONNECTION_INFO'); ?></span> <br/>
    
    <div name="show-on-NToOne">
        <hr class="solid">
        <span class="joined-table-info-label bold" title="DB name of the foreign key field at the local table, connecting to the joined table">
            <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_JOINED_TABLE_MAIN_TABLE'); ?></span> <br/>

        <span class="joined-table-info-label" name="NToOne-foreignKey-label" title="DB name of the foreign key field at the local table, connecting to the joined table">
            <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_JOINED_TABLE_LOCAL_FOREIGN_KEY'); ?></span>
        <input type="text" name="NToOne-foreignKey" /> <br/>

        <hr class="solid">
        <span class="joined-table-info-label bold" title="DB name of the foreign key field at the local table, connecting to the joined table">
            <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_JOINED_TABLE_JOINED_TABLE'); ?></span> <br/>

        <span class="joined-table-info-label" name="NToOne-remoteId-label" title="DB name of the ID field at the joined table">
            <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_JOINED_TABLE_REMOTE_ID'); ?></span>
        <input type="text" name="NToOne-remoteId" /> <br/>
        <hr class="solid">
    </div>

    <div name="show-on-OneToN" hidden>
        <hr class="solid">
        <span class="joined-table-info-label bold" title="DB name of the foreign key field at the local table, connecting to the joined table">
            <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_JOINED_TABLE_JOINED_TABLE'); ?></span> <br/>

        <span class="joined-table-info-label" name="OneToN-foreignKey-label" title="DB name of the foreign key field at the joined table, connecting to the main table">
            <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_JOINED_TABLE_REMOTE_FOREIGN_KEY'); ?></span>
        <input type="text" name="OneToN-foreignKey" /> <br/>
        <hr class="solid">
    </div>
    
    <div name="show-on-NToN" hidden>
        <hr class="solid">
        <span class="joined-table-info-label bold" title="DB name of the foreign key field at the local table, connecting to the joined table">
            <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_JOINED_TABLE_INTERMEDIATE_TABLE'); ?></span> <br/>

        <span class="joined-table-info-label" name="NToN-intermediateTable-label" title="DB name of the connecting table between the local and joined table">
            <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_JOINED_TABLE_INTERMEDIATE_NAME'); ?></span>
        <input type="text" name="NToN-intermediateTable" /> <br/>

        <span class="joined-table-info-label" name="NToN-intermediateLocalKey-label" title="DB name of the foreign key field at the connecting table, connecting to the local table">
            <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_JOINED_TABLE_INTERMEDIATE_LOCAL_KEY'); ?></span>
        <input type="text" name="NToN-intermediateLocalKey" /> <br/>

        <span class="joined-table-info-label" name="NToN-intermediateRemoteKey-label" title="DB name of the foreign key field at the connecting table, connecting to the joined table">
            <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_JOINED_TABLE_INTERMEDIATE_REMOTE_KEY'); ?></span>
        <input type="text" name="NToN-intermediateRemoteKey" /> <br/>

    
        <hr class="solid">
        <span class="joined-table-info-label bold" title="DB name of the foreign key field at the local table, connecting to the joined table">
            <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_JOINED_TABLE_JOINED_TABLE'); ?></span> <br/>
    
        <span class="joined-table-info-label" name="NToN-remoteId-label" title="DB name of the ID field at the joined table">
            <?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_JOINED_TABLE_REMOTE_ID'); ?></span>
        <input type="text" name="NToN-remoteId" /> <br/>
        <hr class="solid">
    </div>
</div>

<script>
    fieldNumber = 0;
    parameterNumber = 0;
    joinedTableNumber = 0;

    dummyForm = document.getElementById("dummyForm");

    function addField() {
        const clone = document.getElementById("template-field-blueprint").cloneNode(true);
        clone.id = "template-field-" + fieldNumber;
        clone.hidden = false;

        remDiv = document.createElement('DIV');
        remDiv.innerHTML = '<button type="button" onclick="removeField(' + fieldNumber + ')"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_REMOVE_FIELD'); ?></button>'
        clone.appendChild(remDiv.firstChild);

        moveUpDiv = document.createElement('DIV');
        moveUpDiv.innerHTML = '<button type="button" onclick="moveUp(' + fieldNumber + ')"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_UP'); ?></button>'
        clone.appendChild(moveUpDiv.firstChild);

        moveDownDiv = document.createElement('DIV');
        moveDownDiv.innerHTML = '<button type="button" onclick="moveDown(' + fieldNumber + ')"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_DOWN'); ?></button>'
        clone.appendChild(moveDownDiv.firstChild);

        document.getElementById("template-fields-area").appendChild(clone);
        
        fieldNumber += 1;

        updateValues();
    }

    function removeField(fieldId) {
        if (confirm('<?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_REMOVE_FIELD_CONFIRM'); ?>')) {
            document.getElementById("template-field-" + fieldId).remove();
            updateValues();
        }

    }

    function addUrlParameter() {
        const clone = document.getElementById("url-parameter-blueprint").cloneNode(true);
        clone.id = "url-parameter-" + parameterNumber;
        clone.hidden = false;

        remDiv = document.createElement('DIV');
        remDiv.innerHTML = '<button type="button" onclick="removeUrlParameter(' + parameterNumber + ')"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_REMOVE_PARAMETER'); ?></button>'
        clone.appendChild(remDiv.firstChild);

        document.getElementById("url-parameters-area").appendChild(clone);
        
        parameterNumber += 1;

        updateValues();
    }

    function removeUrlParameter(parameterId) {
        if (confirm('<?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_REMOVE_PARAMETER_CONFIRM'); ?>')) {
            document.getElementById("url-parameter-" + parameterId).remove();
            updateValues();
        }

    }

    function moveUp(fieldId) {
        fieldElement = document.getElementById("template-field-" + fieldId);

        if (fieldElement.previousElementSibling)
            fieldElement.parentNode.insertBefore(fieldElement, fieldElement.previousElementSibling);
        
        updateValues();
    }

    function moveDown(fieldId) {
        fieldElement = document.getElementById("template-field-" + fieldId);

        if (fieldElement.nextElementSibling)
            fieldElement.parentNode.insertBefore(fieldElement.nextElementSibling, fieldElement);
        
        updateValues();
    }

    function addJoinedTable() {
        const clone = document.getElementById("joined-table-blueprint").cloneNode(true);
        clone.id = "joined-table-" + joinedTableNumber;
        clone.hidden = false;

        div = document.createElement('DIV');
        div.innerHTML = '<button type="button" onclick="removeJoinedTable(' + joinedTableNumber + ')"><?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_REMOVE_JOINED_TABLE'); ?></button>'
        clone.appendChild(div.firstChild);

        document.getElementById("joined-tables-area").appendChild(clone);
        
        joinedTableNumber += 1;

        updateValues();
    }

    function removeJoinedTable(joinedTableId) {
        if (confirm('<?php echo Text::_('COM_NEUKOMTEMPLATING_FORM_REMOVE_JOINED_FIELD_CONFIRM'); ?>')) {
            document.getElementById("joined-table-" + joinedTableId).remove();
            updateValues();
        }
    }

    function retrieveFormValue(valueId) {
        if (document.adminForm[valueId].id == "jform_fields") {
            return retrieveFieldsValue();
        } else if (document.adminForm[valueId].id == "jform_url_parameters") {
            return retrieveUrlParametersValue();
        } else if (document.adminForm[valueId].id == "jform_joined_tables") {
            return retrieveJoinedTablesValue();
        } else {
            dummyInput = dummyForm.querySelector('[name="' + document.adminForm[valueId].name + '"]');

            return (dummyInput.type == "checkbox" ? dummyInput.checked : dummyInput.value);
        }
    }

    function retrieveFieldsValue() {
        fieldString = "";
        templateFieldsArea = document.getElementById("template-fields-area");

        for (let i = 0; i < templateFieldsArea.childNodes.length; i++) {
            field = templateFieldsArea.childNodes[i];

            if (i > 0) {
                fieldString = fieldString.concat(";");
            }

            fieldString = fieldString.concat(field.querySelector('input[name="name"]').value);
            
            fieldString = fieldString.concat(":");

            inputType = field.querySelector('select[name="type"]').value;
            fieldString = fieldString.concat(inputType);
            
            fieldString = fieldString.concat(":");

            if (field.querySelector('input[name="required"]').checked) {
                fieldString = fieldString.concat("1");
            } else {
                fieldString = fieldString.concat("0");
            }
            
            fieldString = fieldString.concat(":");

            if (field.querySelector('input[name="showInForm"]').checked) {
                fieldString = fieldString.concat("1");
            } else {
                fieldString = fieldString.concat("0");
            }
            
            fieldString = fieldString.concat(":");

            fieldString = fieldString.concat(field.querySelector('input[name="displayName"]').value);
            
            fieldString = fieldString.concat(":");

            additionalInfo = "";

            if (inputType == 'select') {
                additionalInfo = field.querySelector('input[name="selectOptions"]').value;
            }
            
            fieldString = fieldString.concat(additionalInfo);
        }

        return fieldString;
    }

    function retrieveUrlParametersValue() {
        parameterString = "";
        urlParametersArea = document.getElementById("url-parameters-area");

        for (let i = 0; i < urlParametersArea.childNodes.length; i++) {
            parameter = urlParametersArea.childNodes[i];

            if (i > 0) {
                parameterString = parameterString.concat(";");
            }

            parameterString = parameterString.concat(parameter.querySelector('input[name="name"]').value);
            
            parameterString = parameterString.concat(":");

            parameterString = parameterString.concat(parameter.querySelector('input[name="default"]').value);
            
            parameterString = parameterString.concat(":");

            if (parameter.querySelector('input[name="insertIntoDb"]').checked) {
                parameterString = parameterString.concat("1");
            } else {
                parameterString = parameterString.concat("0");
            }
        }

        return parameterString;
    }

    function retrieveJoinedTablesValue() {
        joinedTablesString = "";
        joinedTablesArea = document.getElementById("joined-tables-area");

        for (let i = 0; i < joinedTablesArea.childNodes.length; i++) {
            joinedTable = joinedTablesArea.childNodes[i];

            if (i > 0) {
                joinedTablesString = joinedTablesString.concat(";");
            }

            joinedTablesString = joinedTablesString.concat(joinedTable.querySelector('input[name="name"]').value);

            joinedTablesString = joinedTablesString.concat(":");

            joinedTablesString = joinedTablesString.concat(joinedTable.querySelector('input[name="displayField"]').value);

            joinedTablesString = joinedTablesString.concat(":");

            connectionType = joinedTable.querySelector('select[name="type"]').value;
            joinedTablesString = joinedTablesString.concat(connectionType);

            joinedTablesString = joinedTablesString.concat(":");

            connectionInfo = joinedTable.querySelector('div[name="show-on-' + connectionType + '"]');
            connectionInfo.querySelectorAll('input').forEach((connectionInfoInput, index) => {
                if (index > 0) {
                    joinedTablesString = joinedTablesString.concat(",");
                }

                joinedTablesString = joinedTablesString.concat(connectionInfoInput.value);
            });

            joinedTablesString = joinedTablesString.concat(":");

            joinedTablesString = joinedTablesString.concat(joinedTable.querySelector('input[name="foreignFields"]').value);

            joinedTablesString = joinedTablesString.concat(":");

            if (joinedTable.querySelector('input[name="showInForm"]').checked) {
                joinedTablesString = joinedTablesString.concat("1");
            } else {
                joinedTablesString = joinedTablesString.concat("0");
            }

            joinedTablesString = joinedTablesString.concat(":");

            joinedTablesString = joinedTablesString.concat(joinedTable.querySelector('input[name="alias"]').value);

            joinedTablesString = joinedTablesString.concat(":");

            joinedTablesString = joinedTablesString.concat(joinedTable.querySelector('input[name="formName"]').value);
        }

        return joinedTablesString;
    }

    function updateValues() {
        for (let i = 0; i < document.adminForm.length; i++) {
            if (document.adminForm[i].type == "checkbox") {
                document.adminForm[i].checked = retrieveFormValue(i);
            } else if (document.adminForm[i].type != "button") {
                document.adminForm[i].value = retrieveFormValue(i);
            }
        }
    }

    function updateFieldInputVisibility() {
        templateFieldsArea = document.getElementById("template-fields-area");

        for (let i = 0; i < templateFieldsArea.childNodes.length; i++) {
            field = templateFieldsArea.childNodes[i];

            inputHidden = !field.querySelector('input[name="showInForm"]').checked;

            field.querySelector('div[name="show-on-showInForm"]').hidden = inputHidden;

            inputType = field.querySelector('select[name="type"]').value;

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

            visibleInput = joinedTable.querySelector('select[name="type"]').value;

            joinedTable.querySelector('div[name="show-on-' + visibleInput + '"]').hidden = false;
        }
    }

    loadedFields = (document.adminForm.jform_fields.value != "" ? document.adminForm.jform_fields.value.split(";") : []);
    loadedParameters = (document.adminForm.jform_url_parameters.value != "" ? document.adminForm.jform_url_parameters.value.split(";") : []);
    loadedJoinedTables = (document.adminForm.jform_joined_tables.value != "" ? document.adminForm.jform_joined_tables.value.split(";") : []);

    for (let i = 0; i < loadedFields.length; i++) {
        try {
            fieldValues = loadedFields[i].split(":");

            addField();
            newField = document.getElementById("template-fields-area").lastChild;

            if (fieldValues[0] != undefined) {newField.querySelector('input[name="name"]').value = fieldValues[0];}
            if (fieldValues[1] != undefined) {newField.querySelector('select[name="type"]').value = fieldValues[1];}
            if (fieldValues[2] != undefined) {newField.querySelector('input[name="required"]').checked = (fieldValues[2] == "1");}
            if (fieldValues[3] != undefined) {newField.querySelector('input[name="showInForm"]').checked = (fieldValues[3] == "1");}
            if (fieldValues[4] != undefined) {newField.querySelector('input[name="displayName"]').value = fieldValues[4];}
            if (fieldValues[5] != undefined && fieldValues[1] == 'select') {
                newField.querySelector('input[name="selectOptions"]').value = fieldValues[5];
            }
        } catch (error) {
            console.error(error);
        }
    }

    for (let i = 0; i < loadedParameters.length; i++) {
        try {
            parameterValues = loadedParameters[i].split(":");

            addUrlParameter();
            newParameter = document.getElementById("url-parameters-area").lastChild;

            if (parameterValues[0] != undefined) {newParameter.querySelector('input[name="name"]').value = parameterValues[0];}
            if (parameterValues[1] != undefined) {newParameter.querySelector('input[name="default"]').value = parameterValues[1];}
            if (parameterValues[2] != undefined) {newParameter.querySelector('input[name="insertIntoDb"]').checked = (parameterValues[2] == "1");}
        } catch (error) {
            console.error(error);
        }
    }

    for (let i = 0; i < loadedJoinedTables.length; i++) {
        try {
            joinedTableValues = loadedJoinedTables[i].split(":");

            addJoinedTable();
            newJoinedTable = document.getElementById("joined-tables-area").lastChild;

            if (joinedTableValues[0] != undefined) {
                newJoinedTable.querySelector('input[name="name"]').value = joinedTableValues[0];
            }
            if (joinedTableValues[1] != undefined) {
                newJoinedTable.querySelector('input[name="displayField"]').value = joinedTableValues[1];
            }
            if (joinedTableValues[2] != undefined) {
                newJoinedTable.querySelector('select[name="type"]').value = joinedTableValues[2];
            }
            if (joinedTableValues[4] != undefined) {
                newJoinedTable.querySelector('input[name="foreignFields"]').value = joinedTableValues[4];
            }
            if (joinedTableValues[5] != undefined) {
                newJoinedTable.querySelector('input[name="showInForm"]').checked = (joinedTableValues[5] == "1");
            }
            if (joinedTableValues[6] != undefined) {
                newJoinedTable.querySelector('input[name="alias"]').value = joinedTableValues[6];
            }
            if (joinedTableValues[7] != undefined) {
                newJoinedTable.querySelector('input[name="formName"]').value = joinedTableValues[7];
            }
            
            if (joinedTableValues[3] != undefined) {
                connectionInfo = newJoinedTable.querySelector('div[name="show-on-' + joinedTableValues[2] + '"]');
                connectionInfoValues = joinedTableValues[3].split(",");

                connectionInfo.querySelectorAll('input').forEach((connectionInfoInput, index) => {
                    connectionInfoInput.value = connectionInfoValues[index];
                });
            }
        } catch (error) {
            console.error(error);
        }
    }

    updateFieldInputVisibility();
    updateJoinedTableInputVisibility();

    updateValues();
</script>
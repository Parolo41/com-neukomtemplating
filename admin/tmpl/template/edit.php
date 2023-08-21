<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
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

<form action="<?php echo Route::_('index.php?option=com_neukomtemplating&layout=' . $layout . $tmpl . '&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="template-form" class="form-validate" hidden>
    <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', []); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'details', 'Details'); ?>
    <?php echo $this->getForm()->renderField('name'); ?>
    <?php echo $this->getForm()->renderField('tablename'); ?>
    <?php echo $this->getForm()->renderField('fields'); ?>
    <?php echo $this->getForm()->renderField('condition'); ?>
    <?php echo $this->getForm()->renderField('header'); ?>
    <?php echo $this->getForm()->renderField('template'); ?>
    <?php echo $this->getForm()->renderField('footer'); ?>
    <?php echo $this->getForm()->renderField('show_detail_page'); ?>
    <?php echo $this->getForm()->renderField('allow_create'); ?>
    <?php echo $this->getForm()->renderField('allow_edit'); ?>
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
    <label>Fields</label>
    <div id="template-fields-area"></div>
    <button type="button" onclick="addField()">Add Field</button>
    <?php echo $this->getForm()->renderField('condition'); ?>
    <?php echo $this->getForm()->renderField('header'); ?>
    <?php echo $this->getForm()->renderField('template'); ?>
    <?php echo $this->getForm()->renderField('footer'); ?>
    <?php echo $this->getForm()->renderField('show_detail_page'); ?>
    <?php echo $this->getForm()->renderField('allow_create'); ?>
    <?php echo $this->getForm()->renderField('allow_edit'); ?>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'joined_tables_tab', 'Joined Tables'); ?>
    <label>Joined Tables</label>
    <div id="joined-tables-area"></div>
    <button type="button" onclick="addJoinedTable()">Add Joined Table</button>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>

<div id="template-field-blueprint" style="margin-bottom: 16px" hidden>
    <span name="name-label">Name: </span>
    <input type="text" name="name" /> <br/>

    <span name="showInForm-label">Show in form: </span>
    <input type="checkbox" name="showInForm" onchange="updateFieldInputVisibility()" checked /> <br/>

    <div name="show-on-showInForm">
        <span name="type-label">Type: </span>
        <select name="type">
            <option value="text">Text</option>
            <option value="textarea">Textarea</option>
            <option value="number">Number</option>
            <option value="date">Date</option>
            <option value="checkbox">Checkbox</option>
        </select> <br/>

        <span name="required-label">Required: </span>
        <input type="checkbox" name="required" /> <br/>
    </div>
</div>

<div id="joined-table-blueprint" style="margin-bottom: 16px" hidden>
    <span name="name-label">Table name: </span>
    <input type="text" name="name" /> <br/>
    
    <span name="displayField-label">Display field: </span>
    <input type="text" name="displayField" /> <br/>

    <span name="type-label">Connection type: </span>
    <select name="type" onchange="updateJoinedTableInputVisibility()">
        <option value="NToOne">n:1</option>
        <option value="OneToN">1:n</option>
        <option value="NToN">n:n</option>
    </select> <br/>
    
    <div name="show-on-NToOne">
        <span name="NToOne-foreignKey-label">Local foreign key field: </span>
        <input type="text" name="NToOne-foreignKey" /> <br/>

        <span name="NToOne-remoteId-label">Remote ID field: </span>
        <input type="text" name="NToOne-remoteId" /> <br/>
    </div>
    
    <div name="show-on-OneToN" hidden>
        <span name="OneToN-foreignKey-label">Remote foreign key field: </span>
        <input type="text" name="OneToN-foreignKey" /> <br/>
    </div>
    
    <div name="show-on-NToN" hidden>
        <span name="NToN-intermediateTable-label">Intermediate table name: </span>
        <input type="text" name="NToN-intermediateTable" /> <br/>

        <span name="NToN-intermediateLocalKey-label">Intermediate foreign key to local table: </span>
        <input type="text" name="NToN-intermediateLocalKey" /> <br/>

        <span name="NToN-intermediateRemoteKey-label">Intermediate foreign key to remote table: </span>
        <input type="text" name="NToN-intermediateRemoteKey" /> <br/>

        <span name="NToN-remoteId-label">Remote ID field: </span>
        <input type="text" name="NToN-remoteId" /> <br/>
    </div>
    
    <span name="foreignFields-label">Fields (comma separated): </span>
    <input type="text" name="foreignFields" /> <br/>

    <span name="showInForm-label">Show in form: </span>
    <input type="checkbox" name="showInForm" /> <br/>
</div>

<script>
    fieldNumber = 0;
    joinedTableNumber = 0;

    dummyForm = document.getElementById("dummyForm");

    function addField() {
        const clone = document.getElementById("template-field-blueprint").cloneNode(true);
        clone.id = "template-field-" + fieldNumber;
        clone.hidden = false;

        div = document.createElement('DIV');
        div.innerHTML = '<button type="button" onclick="removeField(' + fieldNumber + ')">Remove Field</button>'
        clone.appendChild(div.firstChild);

        document.getElementById("template-fields-area").appendChild(clone);
        
        fieldNumber += 1;

        updateValues();
    }

    function removeField(fieldId) {
        if (confirm('Are you sure you want to remove this field?')) {
            document.getElementById("template-field-" + fieldId).remove();
            updateValues();
        }

    }

    function addJoinedTable() {
        const clone = document.getElementById("joined-table-blueprint").cloneNode(true);
        clone.id = "joined-table-" + joinedTableNumber;
        clone.hidden = false;

        div = document.createElement('DIV');
        div.innerHTML = '<button type="button" onclick="removeJoinedTable(' + joinedTableNumber + ')">Remove Joined Table</button>'
        clone.appendChild(div.firstChild);

        document.getElementById("joined-tables-area").appendChild(clone);
        
        joinedTableNumber += 1;

        updateValues();
    }

    function removeJoinedTable(joinedTableId) {
        if (confirm('Are you sure you want to remove this joined table?')) {
            document.getElementById("joined-table-" + joinedTableId).remove();
            updateValues();
        }
    }

    function retrieveFormValue(valueId) {
        if (document.adminForm[valueId].id == "jform_fields") {
            return retrieveFieldsValue();
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

            fieldString = fieldString.concat(field.querySelector('select[name="type"]').value);
            
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
        }

        return fieldString;
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
    loadedJoinedTables = (document.adminForm.jform_joined_tables.value != "" ? document.adminForm.jform_joined_tables.value.split(";") : []);

    for (let i = 0; i < loadedFields.length; i++) {
        fieldValues = loadedFields[i].split(":");

        if (fieldValues.length != 4) {
            continue;
        }

        addField();
        newField = document.getElementById("template-fields-area").lastChild;

        newField.querySelector('input[name="name"]').value = fieldValues[0];
        newField.querySelector('select[name="type"]').value = fieldValues[1];
        newField.querySelector('input[name="required"]').checked = (fieldValues[2] == "1");
        newField.querySelector('input[name="showInForm"]').checked = (fieldValues[3] == "1");
    }

    for (let i = 0; i < loadedJoinedTables.length; i++) {
        joinedTableValues = loadedJoinedTables[i].split(":");

        if (joinedTableValues.length != 6) {
            continue;
        }

        addJoinedTable();
        newJoinedTable = document.getElementById("joined-tables-area").lastChild;

        newJoinedTable.querySelector('input[name="name"]').value = joinedTableValues[0];
        newJoinedTable.querySelector('input[name="displayField"]').value = joinedTableValues[1];
        newJoinedTable.querySelector('select[name="type"]').value = joinedTableValues[2];
        newJoinedTable.querySelector('input[name="foreignFields"]').value = joinedTableValues[4];
        newJoinedTable.querySelector('input[name="showInForm"]').checked = (joinedTableValues[5] == "1");

        connectionInfo = newJoinedTable.querySelector('div[name="show-on-' + joinedTableValues[2] + '"]');
        connectionInfoValues = joinedTableValues[3].split(",");

        connectionInfo.querySelectorAll('input').forEach((connectionInfoInput, index) => {
            connectionInfoInput.value = connectionInfoValues[index];
        });
    }

    updateFieldInputVisibility();
    updateJoinedTableInputVisibility();
</script>
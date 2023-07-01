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
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>

<form id="dummyForm" name="dummyForm" onchange="updateValues()">
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
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>

<div id="template-field-blueprint" style="margin-bottom: 16px" hidden>
    <span name="name-label">Name: </span>
    <input type="text" name="name" /> <br/>

    <span name="showInForm-label">Show in form: </span>
    <input type="checkbox" name="showInForm" onchange="updateInputVisibility()" checked /> <br/>

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

<script>
    fieldNumber = 0;

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

    function retrieveFormValue(valueId) {
        if (document.adminForm[valueId].id == "jform_fields") {
            fieldString = "";
            templateFieldsArea = document.getElementById("template-fields-area");

            for (let i = 0; i < templateFieldsArea.childNodes.length; i++) {
                field = templateFieldsArea.childNodes[i];

                if (i > 0) {
                    fieldString = fieldString.concat(";");
                }

                fieldString = fieldString.concat(field.querySelector('input[name="name"]').value)
                
                fieldString = fieldString.concat(":");

                fieldString = fieldString.concat(field.querySelector('select[name="type"]').value)
                
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
        } else {
            dummyInput = dummyForm.querySelector('[name="' + document.adminForm[valueId].name + '"]');

            return (dummyInput.type == "checkbox" ? dummyInput.checked : dummyInput.value);
        }
    }

    function updateValues() {
        for (let i = 0; i < document.adminForm.length; i++) {
            if (document.adminForm[i].type == "checkbox") {
                document.adminForm[i].checked = retrieveFormValue(i);
            } else {
                document.adminForm[i].value = retrieveFormValue(i);
            }
        }
    }

    function updateInputVisibility() {
        templateFieldsArea = document.getElementById("template-fields-area");

        for (let i = 0; i < templateFieldsArea.childNodes.length; i++) {
            field = templateFieldsArea.childNodes[i];

            inputHidden = !field.querySelector('input[name="showInForm"]').checked;

            field.querySelector('div[name="show-on-showInForm"]').hidden = inputHidden;
        }
    }

    if (document.adminForm.jform_fields.value != "") {
        loadedFields = document.adminForm.jform_fields.value.split(";");

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
    }

    updateInputVisibility();
</script>
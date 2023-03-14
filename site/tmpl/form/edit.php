<?php

// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Factory;

$root = dirname(dirname(dirname(__FILE__)));
require_once($root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

$recordId = JFactory::getApplication()->input->get('recordId', '', 'int')
?>

<form action="<?php echo Route::_('index.php?option=com_neukomtemplating&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="adminForm" class="form-validate form-vertical">
    <?php
    foreach (explode(',', $this->getModel->getItem->fields) as $field)
    {
        echo '<div id="field-' . $field . '>';
        echo '<label for="' . $field . '">' . $field . ':</label>';
        echo '<input type="text" id="' . $field . '" name="' . $field . '" ><br>';
    }
    ?>

    <input type="hidden" id="templateName" name="templateName" value="<? echo $this->getModel->getItem->templateName ?>">
    <input type="hidden" id="fieldNames" name="fieldNames" value="<? echo $this->getModel->getItem->fields ?>">

    <div class="mb-2">
        <button type="button" class="btn btn-primary" onclick="Joomla.submitbutton('template.save')">
            <span class="fas fa-check" aria-hidden="true"></span>

            <?php echo Text::_('JSAVE'); ?>
        </button>

        <button type="button" class="btn btn-danger" onclick="Joomla.submitbutton('template.cancel')">
            <span class="fas fa-times-cancel" aria-hidden="true"></span>

            <?php echo Text::_('JCANCEL'); ?>
        </button>
    </div>
</form>
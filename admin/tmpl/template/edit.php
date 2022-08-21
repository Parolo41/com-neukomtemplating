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

<form action="<?php echo Route::_('index.php?option=com_neukomtemplating&layout=' . $layout . $tmpl . '&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="template-form" class="form-validate">
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
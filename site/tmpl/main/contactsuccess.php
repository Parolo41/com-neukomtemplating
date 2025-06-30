<?php
use Joomla\CMS\Language\Text;
?>

<div id="neukomtemplating-contactform">
    <h1><?php echo Text::_('COM_NEUKOMTEMPLATING_SUCCESS'); ?></h1>
    <h2><?php echo Text::_('COM_NEUKOMTEMPLATING_CONTACT_SUCCESSFUL'); ?></h2>

    <div id="neukomtemplating-formbuttons">
        <a type="button" class="btn btn-primary" id="backToListButton" href="<?php echo $helper->buildUrl('list'); ?>"><?php echo Text::_('COM_NEUKOMTEMPLATING_BACK'); ?></a>
    </div>
</div>

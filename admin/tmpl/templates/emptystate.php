<?php

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;

$displayData = [
    'textPrefix' => 'COM_NEUKOMTEMPLATING',
    'formURL' => 'index.php?option=com_neukomtemplating',
    'icon' => 'icon-copy',
];
$user = Factory::getApplication()->getIdentity();
if ($user->authorise('core.create', 'com_neukomtemplating') || count($user->getAuthorisedCategories('com_neukomtemplating', 'core.create')) > 0) {
    $displayData['createURL'] = 'index.php?option=com_neukomtemplating&task=template.add';
}
echo LayoutHelper::render('joomla.content.emptystate', $displayData);

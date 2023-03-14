<?php

// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Router\Route;

$root = dirname(dirname(dirname(__FILE__)));
require_once($root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

$loader = new \Twig\Loader\ArrayLoader([
    'template' => $this->getModel()->getItem()->template,
]);
$twig = new \Twig\Environment($loader);

?>

<?php
echo $this->getModel()->getItem()->header;
foreach ($this->getModel()->getItem()->data as $data) {
    echo '<a href="' . Route::_('index.php?option=com_neukomtemplating&task=template.edit&id=' . $this->getModel()->getItem()->id . '&recordId=' . $data->id) . '">Edit</a>';
    echo $twig->render('template', ['data' => $data]);
}
echo $this->getModel()->getItem()->footer;
?>
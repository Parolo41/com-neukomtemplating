<?php

// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

$root = dirname(dirname(dirname(__FILE__)));
require_once($root.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php');

$loader = new \Twig\Loader\ArrayLoader([
    'index' => 'Hello {{ name }}!',
    'template' => $this->getModel()->getItem()->template,
]);
$twig = new \Twig\Environment($loader);
$result = $twig->render('template', ['message' => $this->getModel()->getItem()->message]);

?>
<h2>Hello world!</h2>
<?php 
    echo $result;
?>
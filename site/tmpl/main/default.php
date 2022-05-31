<?php

// No direct access to this file
defined('_JEXEC') or die('Restricted Access');
?>
<h2>Hello world!</h2>
<p><?= $this->getModel()->getItem()->message; ?></p>
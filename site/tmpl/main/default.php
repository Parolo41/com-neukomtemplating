<?php

// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

$root = dirname(dirname(dirname(__FILE__)));
require_once($root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

$item = $this->getModel()->getItem();

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'helper.php');

$itemTemplate = $item->template;

if (strpos($itemTemplate, 'editButton') == false) {
    $itemTemplate = $itemTemplate . '{{editButton | raw}}';
}

$loader = new \Twig\Loader\ArrayLoader([
    'template' => $itemTemplate,
    'detail_template' => $item->detailTemplate,
]);
$twig = new \Twig\Environment($loader);

$emailCloakFilter = new \Twig\TwigFilter('email_cloak', function ($string) {
    return JHtml::_('email.cloak', $string);
});

$twig->addFilter($emailCloakFilter);

$input = Factory::getApplication()->input;

$act = $input->get('act', '', 'string');
$recordId = $input->get('recordId', '', 'string');

$searchTerm = $input->get('searchTerm', '', 'string');

$pageNumber = max($input->get('pageNumber', 1, 'int'), 1);
$pageSize = ($item->enablePagination && $item->pageSize > 0) ? $item->pageSize : max(sizeof($item->data), 1);
$lastPageNumber = ceil(sizeof($item->data) / $pageSize);

if (($this->getModel()->getItem()->allowEdit || $this->getModel()->getItem()->allowCreate) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = Factory::getDbo();
    $input = Factory::getApplication()->input;

    if ($input->get('formAction', '', 'string') == "insert") {
        if (dbInsert($input, $db, $this)) {
            $act = 'list';
        }
    }

    if ($input->get('formAction', '', 'string') == "update") {
        dbUpdate($input, $db, $this);
    }

    if ($input->get('formAction', '', 'string') == "delete") {
        if (dbDelete($input, $db, $this)) {
            $act = 'list';
        }
    }
}

$item = $this->getModel()->getItem();

?>

<?php
    if ($act == 'detail' && $item->showDetailPage && $recordId != '') {
        foreach ($item->data as $data) {
            if ($data->{$item->idFieldName} == $recordId) {
                require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'detailview.php');
                break;
            }
        }
    } elseif ($act == 'edit' && $item->allowEdit && $recordId != '') {
        foreach ($item->data as $data) {
            if ($data->{$item->idFieldName} == $recordId) {
                require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'editview.php');
                break;
            }
        }
    } elseif ($item->userIdLinkField != "" && $item->allowEdit) {
        if (sizeof($item->data) > 0) {
            $data = $item->data[0];
            require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'editview.php');
            ?>
            <script>
                document.getElementById("backToListButton").style.display = "none";
                document.getElementById("deleteRecordButton").style.display = "none";
            </script>
            <?php
        } else {
            echo "<h2>No record found</h2>";
        }
    } elseif ($act == 'new' && $item->allowCreate) {
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'newview.php');
    } else {
        if ($item->enableSearch) { ?>
            <div id="neukomtemplating-search">
                <form name="searchForm" id="searchForm">
                    <label for="searchTerm">Suche</label>
                    <input type="text" name="searchTerm" value="<?php echo $input->get('searchTerm', '', 'string') ?>" />
                    <button type="submit">Suchen</button>
                </form>
                <script>$('#searchForm').submit(function(e) {doSearch(); return false;});</script>
            </div>
        <?php }
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'listview.php');
    }
?>

<form action="<?php echo Route::_(Uri::getInstance()->toString()); ?>" method="get" name="detailNavForm" id="detailNavForm">
    <input type="hidden" name="act" />
    <input type="hidden" name="recordId" />
    <?php if ($item->enableSearch) { echo '<input type="hidden" name="searchTerm" value="' . $searchTerm . '" />'; } ?>
    <?php if ($item->enablePagination) { echo '<input type="hidden" name="pageNumber" value="' . $pageNumber . '" />'; } ?>

    <input type="hidden" name="view" value="main" />
    <input type="hidden" name="templateConfigName" value="<?php echo $item->templateName; ?>" />
</form>
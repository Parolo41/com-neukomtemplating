<?php

// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\HTML\HTMLHelper;

$root = dirname(dirname(dirname(__FILE__)));
require_once($root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

$item = $this->getModel()->getItem();

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'helper.php');

$itemTemplate = $item->template;

if (strpos($itemTemplate, 'editButton') == false && strpos($itemTemplate, 'editLink') == false && strpos($itemTemplate, 'editUrl') == false) {
    $itemTemplate = $itemTemplate . '{{editButton | raw}}';
}

$loader = new \Twig\Loader\ArrayLoader([
    'template' => $itemTemplate,
    'detail_template' => $item->detailTemplate,
    'contact_display_name' => $item->contactDisplayName,
]);
$twig = new \Twig\Environment($loader);
$twig->addExtension(new Twig\Extra\Intl\IntlExtension());

$emailCloakFilter = new \Twig\TwigFilter('email_cloak', function ($string, $displayText = '') {
    return HTMLHelper::_('email.cloak', $string, 1, ($displayText == '' ? $string : $displayText));
});

$twig->addFilter($emailCloakFilter);

$input = Factory::getApplication()->input;

if (($this->getModel()->getItem()->allowEdit || $this->getModel()->getItem()->allowCreate) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = Factory::getContainer()->get('DatabaseDriver');
    $input = Factory::getApplication()->input;
    $app = Factory::getApplication();

    if ($input->get('formAction', '', 'string') == "insert") {
        if ($lastRowId = dbInsert($input, $db, $this)) {
            if ($item->formSendBehaviour == 'edit_on_insert' || $item->formSendBehaviour == 'edit_on_both') {
                $act = 'edit';
                $recordId = strval($lastRowId);

                $input->set('act', 'edit');
                $input->set('recordId', $recordId);

                setUrl(buildUrl($this, 'edit', recordId: $recordId));
            } else {
                $act = 'list';

                $input->set('act', 'list');

                setUrl(buildUrl($this, 'list'));
            }
        } else {
            $app->enqueueMessage(Text::_('COM_NEUKOMTEMPLATING_ERROR_INSERT'), 'error');
        }
    }

    if ($input->get('formAction', '', 'string') == "update") {
        if (dbUpdate($input, $db, $this)) {
            if ($item->formSendBehaviour == 'edit_on_update' || $item->formSendBehaviour == 'edit_on_both') {
                $act = 'edit';

                $input->set('act', 'edit');

                setUrl(buildUrl($this, 'edit', recordId: $input->get('recordId', 0, 'INT')));
            } else {
                $act = 'list';

                $input->set('act', 'list');

                setUrl(buildUrl($this, 'list'));
            }
        } else {
            $app->enqueueMessage(Text::_('COM_NEUKOMTEMPLATING_ERROR_UPDATE'), 'error');
        }
    }

    if ($input->get('formAction', '', 'string') == "delete") {
        if (dbDelete($input, $db, $this)) {
            $act = 'list';

            $input->set('act', 'list');

            setUrl(buildUrl($this, 'list'));
        } else {
            $app->enqueueMessage(Text::_('COM_NEUKOMTEMPLATING_ERROR_DELETE'), 'error');
        }
    }

    if ($input->get('formAction', '', 'string') == "message") {
        if (sendMessage($input, $db, $this)) {
            $act = 'list';

            $input->set('act', 'list');

            setUrl(buildUrl($this, 'list'));
        } else {
            $app->enqueueMessage(Text::_('COM_NEUKOMTEMPLATING_ERROR_MESSAGE'), 'error');
        }
    }
    
    $item = $this->getModel()->getItem();
}

$act = $input->get('act', '', 'string');
$recordId = $input->get('recordId', 0, 'INT');

$searchTerm = $input->get('searchTerm', '', 'string');

$pageNumber = max($input->get('pageNumber', 1, 'INT'), 1);
$pageSize = $item->pageSize;
$lastPageNumber = $item->lastPageNumber;

?>

<?php
    $active = Factory::getApplication()->getMenu()->getActive();
    $pageHeading = $active->getParams()->get('page_heading');

    echo $pageHeading != '' ? '<h1 class="title">' . $pageHeading . '</h1>' : '';

    if ($item->userIdLinkField != "") {
        if (!$item->allowEdit) {
            echo "<p>Warning: Editing is not enabled. Changes can't be saved.</p>";
        }

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
    } elseif ($act == 'detail' && $item->showDetailPage && $recordId != 0 && !empty($item->data[$recordId])) {
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'detailview.php');
    } elseif ($act == 'edit' && $item->allowEdit && $recordId != 0 && !empty($item->data[$recordId])) {
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'editview.php');
    } elseif ($act == 'new' && $item->allowCreate) {
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'newview.php');
    } elseif ($act == 'contact' && $item->contactEmailField != '' && $recordId != 0 && !empty($item->data[$recordId])) {
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'contactview.php');
    } else {
        if ($item->enableSearch) { ?>
            <div id="neukomtemplating-search">
                <form name="searchForm" id="searchForm">
                    <label for="searchTerm">Suche</label>
                    <input type="text" name="searchTerm" value="<?php echo $input->get('searchTerm', '', 'string') ?>" />
                    <button type="submit" class="btn btn-primary"><?php echo Text::_('COM_NEUKOMTEMPLATING_SEARCH'); ?></button>
                </form>
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

    <?php
        foreach ($item->urlParameters as $parameterName => $urlParameter) {
            $parameterValue = $input->get($parameterName, false, 'string');

            if ($parameterValue != false) {
                echo '<input type="hidden" name="' . $parameterName . '" value="' . $parameterValue . '" />';
            }
        }
    ?>

    <input type="hidden" name="view" value="main" />
    <input type="hidden" name="templateConfigName" value="<?php echo $item->templateName; ?>" />
</form>
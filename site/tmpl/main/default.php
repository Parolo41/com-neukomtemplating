<?php

// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\HTML\HTMLHelper;
use Neukom\Component\NeukomTemplating\Site\Helper\Helper;

$root = dirname(dirname(dirname(__FILE__)));
require_once($root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

$item = $this->getModel()->getItem();

$helper = Helper::getInstance();

$itemTemplate = $item->template;

if ($item->allowEdit && strpos($itemTemplate, 'editButton') == false && strpos($itemTemplate, 'editLink') == false && strpos($itemTemplate, 'editUrl') == false) {
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
        if ($lastRowId = $helper->dbInsert($input)) {
            if ($item->formSendBehaviour == 'edit_on_insert' || $item->formSendBehaviour == 'edit_on_both') {
                $act = 'edit';
                $recordId = strval($lastRowId);

                $input->set('act', 'edit');
                $input->set('recordId', $recordId);

                $helper->setUrl($helper->buildUrl('edit', recordId: $recordId));
            } else {
                $act = 'list';

                $input->set('act', 'list');

                $helper->setUrl($helper->buildUrl('list'));
            }
        } else {
            $app->enqueueMessage(Text::_('COM_NEUKOMTEMPLATING_ERROR_INSERT'), 'error');
        }
    }

    if ($input->get('formAction', '', 'string') == "update") {
        if ($helper->dbUpdate($input)) {
            if ($item->formSendBehaviour == 'edit_on_update' || $item->formSendBehaviour == 'edit_on_both') {
                $act = 'edit';

                $input->set('act', 'edit');

                $helper->setUrl($helper->buildUrl('edit', recordId: $input->get('recordId', 0, 'INT')));
            } else {
                $act = 'list';

                $input->set('act', 'list');

                $helper->setUrl($helper->buildUrl('list'));
            }
        } else {
            $app->enqueueMessage(Text::_('COM_NEUKOMTEMPLATING_ERROR_UPDATE'), 'error');
        }
    }

    if ($input->get('formAction', '', 'string') == "delete") {
        if ($helper->dbDelete($input)) {
            $act = 'list';

            $input->set('act', 'list');

            $helper->setUrl($helper->buildUrl('list'));
        } else {
            $app->enqueueMessage(Text::_('COM_NEUKOMTEMPLATING_ERROR_DELETE'), 'error');
        }
    }

    if ($input->get('formAction', '', 'string') == "message") {
        if ($helper->sendMessage($input)) {
            $act = 'list';

            $input->set('act', 'list');

            $helper->setUrl($helper->buildUrl('list'));
        } else {
            $app->enqueueMessage(Text::_('COM_NEUKOMTEMPLATING_ERROR_MESSAGE'), 'error');
        }
    }
    
    $item = $this->getModel()->getItem();
}

$act = $input->get('act', '', 'string');
$recordId = $input->get('recordId', 0, 'INT');

$searchTerm = $input->get('searchTerm', '', 'string');

$pageSize = $item->pageSize;
$lastPageNumber = $item->lastPageNumber;
$pageNumber = min(max($input->get('pageNumber', 1, 'INT'), 1), $lastPageNumber);

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
            $recordId = array_key_first($item->data);
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

<script>
    <?php if ($item->allowEdit || $item->allowCreate) { ?>

    function openNewForm() {
        $('#detailNavForm input[name="act"]').val('new');
        submitNavForm();
    }

    function openEditForm(recordId) {
        $('#detailNavForm input[name="act"]').val('edit');
        $('#detailNavForm input[name="recordId"]').val(recordId);
        submitNavForm();
    }

    function confirmDelete() {
        document.getElementById("formAction").value = 'delete';

        document.getElementById("neukomtemplating-formbuttons").style.display = 'none';
        document.getElementById("neukomtemplating-deletebuttons").style.display = 'block';
    }

    function cancelDelete() {
        document.getElementById("formAction").value = "update";

        document.getElementById("neukomtemplating-formbuttons").style.display = 'block';
        document.getElementById("neukomtemplating-deletebuttons").style.display = 'none';
    }

    <?php } ?>

    function openDetailPage(recordId) {
        $('#detailNavForm input[name="act"]').val('detail');
        $('#detailNavForm input[name="recordId"]').val(recordId);
        submitNavForm();
    }

    function openListView() {
        $('#detailNavForm input[name="act"]').val('list');
        submitNavForm();
    }

    function openContactForm(recordId) {
        $('#detailNavForm input[name="act"]').val('contact');
        $('#detailNavForm input[name="recordId"]').val(recordId);
        submitNavForm();
}

    function goToPage(pageNumber) {
        $('#detailNavForm input[name="pageNumber"]').val(pageNumber);
        submitNavForm();
    }

    function doSearch() {
        $('#detailNavForm input[name="pageNumber"]').val(1);
        $('#detailNavForm input[name="searchTerm"]').val($('#searchForm input[name="searchTerm"]').val());
        submitNavForm();
    }

    function submitNavForm() {
        if ($('#detailNavForm input[name="act"]').val() == '') {
            $('#detailNavForm input[name="act"]').remove()
        }

        if ($('#detailNavForm input[name="recordId"]').val() == '') {
            $('#detailNavForm input[name="recordId"]').remove()
        }

        if ($('#detailNavForm input[name="searchTerm"]').val() == '') {
            $('#detailNavForm input[name="searchTerm"]').remove()
        }

        $('#detailNavForm').submit();
    }
</script>
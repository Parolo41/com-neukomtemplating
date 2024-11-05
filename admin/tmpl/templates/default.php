<?php
\defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>

<form action="<?php echo Route::_('index.php?option=com_neukomtemplating'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php if (empty($this->items)) : ?>
                    <div class="alert alert-warning">
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>
                <?php else : ?>
                    <table class="table" id="templatesList">
                        <thead>
                            <tr>
                                <th scope="col" style="width:1%" class="text-center d-none d-md-table-cell">
                                    <?php echo Text::_('COM_NEUKOMTEMPLATING_TABLE_TABLEHEAD_NAME'); ?>
                                </th>
                                <th scope="col">
                                    <?php echo Text::_('COM_NEUKOMTEMPLATING_TABLE_TABLEHEAD_ID'); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $n = count($this->items);
                            foreach ($this->items as $i => $item) :
                            ?>
                                <tr class="row<?php echo $i % 2; ?>">
                                    <th scope="row" class="has-context">
                                        <div>
                                            <?php echo $this->escape($item->name); ?>
                                        </div>
                                        <?php $editIcon = '<span class="fa fa-pencil-square mr-2" aria-hidden="true"></span>'; ?>
                                        <a class="hasTooltip" href="<?php echo Route::_('index.php?option=com_neukomtemplating&task=template.edit&id=' . (int) $item->id); ?>" title="<?php echo Text::_('JACTION_EDIT'); ?> <?php echo $this->escape(addslashes($item->name)); ?>">
                                            <?php echo $editIcon; ?><?php echo $this->escape($item->name); ?></a>
                                    </th>
                                    <td class="d-none d-md-table-cell">
                                        <?php echo $item->id; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
                <input type="hidden" name="task" value="">
                <input type="hidden" name="boxchecked" value="0">
                <?php echo HTMLHelper::_('form.token'); ?>
            </div>
        </div>
    </div>
</form>
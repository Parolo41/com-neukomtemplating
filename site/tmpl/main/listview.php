<?php
use Joomla\CMS\Language\Text;
?>

<div id="neukomtemplating-listview">
    <?php
        $newUrl = $helper->buildUrl('new');
        echo $item->allowCreate ? '<a href="' . $newUrl . '" class="btn btn-primary"  style="margin-top: 1em">' . Text::_('COM_NEUKOMTEMPLATING_NEW') . '</a>' : "";
        echo $item->header;

        foreach ($item->data as $data) {
            $detailUrl = $helper->buildUrl('detail', recordId: $data->{$item->idFieldName});
            $editUrl = $helper->buildUrl('edit', recordId: $data->{$item->idFieldName});
            $contactUrl = $helper->buildUrl('contact', recordId: $data->{$item->idFieldName});

            $twigParams = [
                'data' => $data,
                'urlParameters' => $item->urlParameters,
                'detailUrl' => $detailUrl,
                'editUrl' => $editUrl,
                'contactUrl' => $contactUrl,
                'detailLink' => '<a href="' . $detailUrl . '">' . Text::_('COM_NEUKOMTEMPLATING_DETAIL') . '</a>',
                'editLink' => '<a href="' . $editUrl . '">' . Text::_('COM_NEUKOMTEMPLATING_EDIT') . '</a>',
                'contactLink' => '<a href="' . $contactUrl . '">' . Text::_('COM_NEUKOMTEMPLATING_CONTACT') . '</a>',
                'detailButton' => '<a href="' . $detailUrl . '" class="btn btn-primary">' . Text::_('COM_NEUKOMTEMPLATING_DETAIL') . '</a>',
                'editButton' => '<a href="' . $editUrl . '" class="btn btn-primary">' . Text::_('COM_NEUKOMTEMPLATING_EDIT') . '</a>',
                'contactButton' => '<a href="' . $contactUrl . '" class="btn btn-primary">' . Text::_('COM_NEUKOMTEMPLATING_CONTACT') . '</a>',
            ];
            echo $twig->render('template', array_merge($twigParams, $item->aliases));
        }
        echo $item->footer;
    ?>
</div>

<?php if ($item->enablePagination) { ?>
    <div id="neukomtemplating-page-control" style="margin-top: 1em">
        <?php if ($pageNumber <= 1) { ?>
            <span class="btn">&#8678;</span>
        <?php } else { ?>
            <a href="<?php echo $helper->buildUrl('list', targetPage: $pageNumber - 1); ?>" class="btn btn-primary">&#8678;</a>
        <?php } ?>
        

        <?php for ($p = 1; $p <= $lastPageNumber; $p++) {
            if ($p == $pageNumber) {
                echo '<span style="margin: 0.25em">' . $p . '</span>';
            } else {
                echo '<a href="' . $helper->buildUrl('list', targetPage: $p) . '" style="margin: 0.25em">' . $p . '</a>';
            }
        } ?>
        
        <?php if ($pageNumber >= $lastPageNumber) { ?>
            <span class="btn">&#8680;</span>
        <?php } else { ?>
            <a href="<?php echo $helper->buildUrl('list', targetPage: $pageNumber + 1); ?>" class="btn btn-primary">&#8680;</a>
        <?php } ?>
    </div>
<?php } ?>

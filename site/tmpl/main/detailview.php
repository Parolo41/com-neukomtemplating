<?php
use Joomla\CMS\Language\Text;
?>

<div id="neukomtemplating-detailview">
    <?php
        $detailUrl = buildUrl($this, 'detail', recordId: $data->{$item->idFieldName});
        $editUrl = buildUrl($this, 'edit', recordId: $data->{$item->idFieldName});
        $contactUrl = buildUrl($this, 'contact', recordId: $data->{$item->idFieldName});

        $twigParams = [
            'data' => $item->data[$recordId],
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
        echo $twig->render('detail_template', array_merge($twigParams, $item->aliases));
    ?>

    <div id="neukomtemplating-formbuttons">
        <a type="button" class="btn btn-primary" id="backToListButton" href="<?php echo buildUrl($this, 'list'); ?>"><?php echo Text::_('COM_NEUKOMTEMPLATING_BACK'); ?></a>
    </div>
</div>
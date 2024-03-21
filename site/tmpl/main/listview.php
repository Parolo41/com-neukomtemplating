<div id="neukomtemplating-listview">
    <?php
        echo $item->allowCreate ? '<button onClick="openNewForm()">Neu</button>' : "";
        echo $item->header;
        foreach ($item->data as $data) {
            $twigParams = [
                'data' => $data, 
                'detailButton' => $item->showDetailPage ? '<button onClick="openDetailPage(' . $data->{$item->idFieldName} . ')">Detail</button>' : "",
                'editButton' => $item->allowEdit ? '<button onClick="openEditForm(' . $data->{$item->idFieldName} . ')">Editieren</button>' : "",
                'detailLink' => 'javascript:openDetailPage(' . $data->{$item->idFieldName} . ')',
                'editLink' => 'javascript:openEditForm(' . $data->{$item->idFieldName} . ')',
            ];
            echo $twig->render('template', array_merge($twigParams, $item->aliases));
        }
        echo $item->footer;
    ?>
</div>

<?php if ($item->enablePagination) { ?>
    <div id="neukomtemplating-page-control">
        <button onClick="goToPage(<?php echo $pageNumber - 1; ?>)" <?php echo $pageNumber <= 1 ? 'disabled' : '' ?>>&#8678;</button>

        <?php for ($p = 1; $p <= $lastPageNumber; $p++) {
            if ($p == $pageNumber) {
                echo $p;
            } else {
                echo '<a href="javascript:goToPage(' . $p . ')">' . $p . '</a>';
            }
        } ?>

        <button onClick="goToPage(<?php echo $pageNumber + 1; ?>)" <?php echo $pageNumber >= $lastPageNumber ? 'disabled' : '' ?>>&#8680;</button>
    </div>
<?php } ?>

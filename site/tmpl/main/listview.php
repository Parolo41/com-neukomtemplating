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
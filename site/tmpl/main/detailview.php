<div id="neukomtemplating-detailview">
    <?php
        $twigParams = [
            'data' => $data,
            'urlParameters' => $item->urlParameters,
            'detailButton' => $item->showDetailPage ? '<button onClick="openDetailPage(' . $recordId . ')">Detail</button>' : "",
            'editButton' => $item->allowEdit ? '<button onClick="openEditForm(' . $recordId . ')">Editieren</button>' : "",
            'detailLink' => 'javascript:openDetailPage(' . $recordId . ')',
            'editLink' => 'javascript:openEditForm(' . $recordId . ')',
        ];
        echo $twig->render('detail_template', array_merge($twigParams, $item->aliases));
    ?>

    <div id="neukomtemplating-formbuttons">
        <button type="button" id="backToListButton" onClick="openListView()">Zurück</button>
    </div>
</div>
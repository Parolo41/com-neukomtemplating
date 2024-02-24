<div id="neukomtemplating-detailview">
    <?php
        echo $twig->render('detail_template', ['data' => $data]);
    ?>

    <div id="neukomtemplating-formbuttons">
        <button type="button" id="backToListButton" onClick="openListView()">Zurück</button>
    </div>
</div>
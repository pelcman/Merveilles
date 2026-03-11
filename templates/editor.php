<?php
/**
 * @var int $floor
 * @var array $editorData
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <script src="/js/jsme/mootools-1.2.4-core-yc.js"></script>
    <script src="/js/jsme/mootools-1.2.4.2-more.js"></script>
    <script src="/js/jsme/gui/MavDialog.js"></script>
    <link rel="stylesheet" href="/js/jsme/gui/mavdialog.css">
    <script src="/js/jsme/gui/toolbar.js"></script>
    <script src="/js/jsme/gui/window.js"></script>
    <script src="/js/jsme/mapeditor.js"></script>
    <script src="/js/jsme/mapeditortile.js"></script>
    <script src="/js/jsme/mapeditor_gui.js"></script>
    <script src="/js/jsme/mapeditor_map.js"></script>
    <script src="/js/jsme/plugins/merveilles/plugin.js"></script>
    <title>Map Editor - Merveilles</title>
</head>
<body style="overflow:hidden;">
    <div id="editor_container"></div>
    <script>
        MerveillesME_Init('editor_container', <?= json_encode($editorData) ?>);
    </script>
</body>
</html>

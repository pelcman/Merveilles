<?php
/**
 * @var array $data
 * @var array $state
 * @var string $classClient
 * @var int $width
 * @var int $height
 */
?>
<!DOCTYPE html>
<html>
<head>
    <?php require __DIR__ . '/partials/head.php'; ?>
    <script src="/js/merveilles.js"></script>
    <title>Merveilles Portable</title>
</head>
<body id="body">
    <div class="clientPortable <?= htmlspecialchars($classClient) ?>">
        <a href="#" class="ui_rightbtn" id="ui_right_chat" onclick="toggle_visibility('guide');"></a>
        <a href="#" class="ui_rightbtn" id="ui_right_map" onclick="toggle_visibility('spellbook');"></a>
        <a href="/logout" class="ui_rightbtn" id="ui_right_logout"></a>

        <?php require __DIR__ . '/partials/guide.php'; ?>
        <?php require __DIR__ . '/partials/spellbook.php'; ?>

        <?php require_once __DIR__ . '/partials/audio.php'; ?>
        <?php renderAudioPlayer('game', $state['floor']); ?>

        <script>
            Merveilles.setViewport(<?= $width ?>, <?= $height ?>);
            Merveilles.init(<?= json_encode($data, JSON_THROW_ON_ERROR) ?>);
        </script>
    </div>
    <menu></menu>
</body>
</html>

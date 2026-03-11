<?php
/** @var string|null $error */
require_once __DIR__ . '/partials/audio.php';
?>
<!DOCTYPE html>
<html>
<head>
    <?php require __DIR__ . '/partials/head.php'; ?>
    <title>Merveilles Portable</title>
</head>
<body class="loginBody">
    <?php renderAudioPlayer('login'); ?>

    <div class="clientPortable" style="z-index:9000">
        <a href="#" class="ui_rightbtn" id="ui_right_chat" style="z-index:9002"></a>
        <a href="/admin" class="ui_rightbtn" id="ui_right_map" style="z-index:9002" target="_blank"></a>
        <a href="/logout" class="ui_rightbtn" id="ui_right_logout" style="z-index:9002"></a>

        <div id="scrolling"></div>

        <span class="logo">merveilles</span>

        <?php if (!empty($error)): ?>
            <p style="color:#c44; text-align:center; font-size:10px; padding:4px;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form action="/login" method="POST" autocomplete="off" class="login">
            <input type="text" name="username" maxlength="3" size="3" class="usr" placeholder="usr">
            <input type="password" name="password" maxlength="3" size="3" class="pwd" placeholder="pwd">
            <input type="submit" name="submit" value="" class="sub">
            <span>
                <a href="about:blank" target="_blank" style="color:#6c5c67">DEEO</a> ,
                <a href="http://xxiivv.com" target="_blank" style="color:#6c5c67">XXIIVV</a> ,
                <a href="about:blank" target="_blank" style="color:#6c5c67">OLDHOME</a>
            </span>
        </form>
    </div>
</body>
</html>

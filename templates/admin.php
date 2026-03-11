<?php
/**
 * @var string $resetUser
 * @var string $resetMessage
 * @var array $leaderboard
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Merveilles Portable | Admin</title>
    <style>
        body  { font: 10pt/20px Arial, sans-serif; }
        table { border: 1px solid #000; width: 100%; }
        td    { border-bottom: 1px dashed #999; padding: 5px; }
        hr    { border: 0; border-bottom: 5px solid #72dec2; }
    </style>
</head>
<body>

<h1>Reset Player Location</h1><hr>
<?php if ($resetMessage): ?>
    <p><?= htmlspecialchars($resetMessage) ?></p>
<?php endif; ?>

<form action="/admin" method="GET">
    Username: <input type="text" name="User" maxlength="3">
    <input type="submit" value="Warp to F3">
</form>

<h1>Leaderboards</h1><hr>
<table>
    <tr style="font-weight:bold">
        <td width="20"></td>
        <td width="20">F</td>
        <td width="100">Username</td>
        <td>XP</td>
        <td>Mobs Killed</td>
        <td>Efficiency</td>
    </tr>
    <?php foreach ($leaderboard as $row):
        $skill = $row['kill'] > 0 ? (int) floor($row['xp'] / $row['kill']) : 0;
        $head = (int) $row['avatar_head'] * 8;
        $body = (int) $row['avatar_body'] * 8;
    ?>
    <tr>
        <td>
            <div style="height:8px; width:16px; background-image:url('/img/avatars.gif'); background-position:0px <?= $head ?>px"></div>
            <div style="height:8px; width:16px; background-image:url('/img/avatars.gif'); background-position:16px <?= $body ?>px"></div>
        </td>
        <td><?= (int) $row['floor'] ?></td>
        <td><b><?= htmlspecialchars($row['mv_name']) ?></b></td>
        <td><?= (int) $row['xp'] ?> XP</td>
        <td><?= (int) $row['kill'] ?></td>
        <td>Level <?= $skill ?></td>
    </tr>
    <?php endforeach; ?>
</table>

</body>
</html>

<?php
/** @var array $state */
$playerlv = $state['level'] ?? 0;
$warp1 = $state['warp1'] ?? 0;
?>
<div id="spellbook">
    <?php if ($warp1 > 0): ?>
    <div class="spell" id="cast1">
        <a href="/api/cast?cast=1"><div></div></a>
        <p class="spellname">blue warp</p>
        <p>5mp</p>
    </div>
    <?php endif; ?>

    <?php if ($playerlv > 2): ?>
    <div class="spell" id="cast9">
        <a href="/api/cast?cast=3"><div></div></a>
        <p class="spellname">Junction</p>
        <p>Lvl 3</p>
    </div>
    <?php endif; ?>
</div>

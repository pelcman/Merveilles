<?php
function renderAudioPlayer(string $context, int $floor = 1): void
{
    if ($context === 'login') {
        $track = '06';
    } else {
        if ($floor < 3)       $track = '01';
        elseif ($floor < 4)   $track = '02';
        elseif ($floor < 9)   $track = '03';
        elseif ($floor < 14)  $track = '04';
        elseif ($floor < 24)  $track = '05';
        else                  $track = '06';
    }
    ?>
    <audio id="music" src="/audio/merveilles_<?= $track ?>.mp3" loop preload="auto"></audio>
    <script>var _m=document.getElementById('music');_m.volume=0.1;_m.play().catch(function(){});</script>
    <div class="audioControls">
        <a href="#" id="audio_toggle" onclick="var m=document.getElementById('music'); if(m.paused){m.play();m.dataset.muted=''}else{m.pause();m.dataset.muted='1'} return false;"></a>
    </div>
    <?php
}

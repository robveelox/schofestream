<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require_auth_page();
$id = trim((string)($_GET['id'] ?? ''));
if ($id === '') {
    http_response_code(400);
    exit('Missing item id.');
}
render_header('Now Playing', false, 'watch-page');
?>
<section id="playerApp" class="player-app" data-id="<?= e($id) ?>">
    <div class="player-topbar player-chrome" id="playerTopbar">
        <button class="icon-btn" id="playerBack" type="button" aria-label="Go back">←</button>
        <div><div class="eyebrow">NOW PLAYING</div><div id="playerTitle" class="player-title" aria-live="polite">Loading…</div></div>
    </div>

    <div class="video-wrap" id="videoWrap">
        <video id="video" playsinline preload="metadata" aria-label="Schofestream video player"></video>
        <button class="player-center-toggle player-chrome" id="centerToggle" type="button" aria-label="Play or pause">▶</button>

        <div class="player-controls player-chrome" id="playerControls">
            <input id="seekBar" class="seek-bar" type="range" min="0" max="1000" value="0" aria-label="Seek through video">
            <div class="control-row">
                <div class="control-group">
                    <button class="control-btn" id="playPause" type="button" aria-label="Play">▶</button>
                    <button class="control-btn" id="skipBack" type="button" aria-label="Back 10 seconds">↶10</button>
                    <button class="control-btn" id="skipForward" type="button" aria-label="Forward 10 seconds">10↷</button>
                    <button class="control-btn" id="muteButton" type="button" aria-label="Mute">🔊</button>
                    <input id="volumeBar" class="volume-bar" type="range" min="0" max="1" step="0.05" value="1" aria-label="Volume">
                    <span class="time-label" aria-live="off"><span id="currentTime">0:00</span> / <span id="durationTime">0:00</span></span>
                </div>
                <div class="control-group player-options">
                    <button class="control-btn text-control" id="audioButton" type="button" aria-haspopup="menu" aria-expanded="false">Audio</button>
                    <button class="control-btn text-control" id="subtitleButton" type="button" aria-haspopup="menu" aria-expanded="false">CC</button>
                    <button class="control-btn text-control" id="qualityButton" type="button" aria-haspopup="menu" aria-expanded="false">Auto</button>
                    <button class="control-btn" id="fullscreenButton" type="button" aria-label="Fullscreen">⛶</button>
                </div>
            </div>
        </div>

        <div class="player-menu" id="audioMenu" role="menu" aria-label="Audio tracks" hidden></div>
        <div class="player-menu" id="subtitleMenu" role="menu" aria-label="Subtitles" hidden></div>
        <div class="player-menu" id="qualityMenu" role="menu" aria-label="Playback quality" hidden></div>

        <div id="playerLoading" class="player-overlay"><span class="spinner"></span><span>Preparing stream…</span></div>
        <div id="playerBuffering" class="player-buffering" hidden><span class="spinner"></span><span>Buffering…</span></div>
        <div id="playerError" class="player-overlay error-overlay" hidden></div>

        <div id="nextEpisode" class="next-episode" hidden>
            <div class="eyebrow">UP NEXT</div>
            <h2 id="nextEpisodeTitle">Next episode</h2>
            <p id="nextEpisodeMeta" class="muted"></p>
            <div class="next-countdown">Starting in <strong id="nextCountdown">10</strong>s</div>
            <div class="next-actions"><button class="btn btn-primary" id="playNext" type="button">Play now</button><button class="btn btn-secondary" id="cancelNext" type="button">Cancel</button></div>
        </div>
    </div>
</section>
<script src="https://cdn.jsdelivr.net/npm/hls.js@1.7.3/dist/hls.min.js"></script>
<?php render_footer(['/assets/js/player.js']); ?>

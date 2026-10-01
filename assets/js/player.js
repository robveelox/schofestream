(async () => {
  const root = document.getElementById('playerApp');
  const video = document.getElementById('video');
  if (!root || !video) return;

  const $ = id => document.getElementById(id);
  const loading = $('playerLoading');
  const buffering = $('playerBuffering');
  const errorBox = $('playerError');
  const title = $('playerTitle');
  const seekBar = $('seekBar');
  const playPause = $('playPause');
  const centerToggle = $('centerToggle');
  const skipBack = $('skipBack');
  const skipForward = $('skipForward');
  const muteButton = $('muteButton');
  const volumeBar = $('volumeBar');
  const fullscreenButton = $('fullscreenButton');
  const currentTime = $('currentTime');
  const durationTime = $('durationTime');
  const nextBox = $('nextEpisode');
  const nextTitle = $('nextEpisodeTitle');
  const nextMeta = $('nextEpisodeMeta');
  const nextCountdown = $('nextCountdown');
  const playNext = $('playNext');
  const cancelNext = $('cancelNext');
  const audioButton = $('audioButton');
  const subtitleButton = $('subtitleButton');
  const qualityButton = $('qualityButton');
  const audioMenu = $('audioMenu');
  const subtitleMenu = $('subtitleMenu');
  const qualityMenu = $('qualityMenu');

  $('playerBack')?.addEventListener('click', () => history.length > 1 ? history.back() : (location.href = '/'));

  let info = null;
  let hls = null;
  let nextEpisode = null;
  let nextPromise = null;
  let lastReport = 0;
  let started = false;
  let failed = false;
  let hideTimer = 0;
  let countdownTimer = 0;
  let countdownValue = 10;
  let networkRetries = 0;
  let mediaRetries = 0;
  let playbackController = null;
  let switching = false;

  const preferences = { audio: null, subtitle: null, bitrate: 0 };

  const eventPayload = event => ({
    event,
    csrf: Schofestream.csrf,
    itemId: root.dataset.id,
    mediaSourceId: info?.mediaSourceId || '',
    playSessionId: info?.playSessionId || '',
    playMethod: info?.playMethod || 'Transcode',
    positionSeconds: video.currentTime || 0,
    isPaused: video.paused,
    isMuted: video.muted,
    volumeLevel: Math.round(video.volume * 100),
    audioStreamIndex: info?.selectedAudio ?? null,
    subtitleStreamIndex: info?.selectedSubtitle ?? -1,
  });

  const report = async (event = 'progress', beacon = false) => {
    if (!info) return;
    const payload = eventPayload(event);
    if (beacon && navigator.sendBeacon) {
      navigator.sendBeacon('/api/playback-event.php', new Blob([JSON.stringify(payload)], { type: 'application/json' }));
      return;
    }
    try {
      await Schofestream.api('/api/playback-event.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
    } catch (_) {}
  };

  const fail = (message, technical = '') => {
    switching = false;
    failed = true;
    loading.hidden = true;
    buffering.hidden = true;
    errorBox.hidden = false;
    const tech = technical ? `<small class="player-tech">${Schofestream.esc(technical)}</small>` : '';
    errorBox.innerHTML = `<strong>Playback failed</strong><span>${Schofestream.esc(message)}</span>${tech}<div class="player-error-actions"><button class="btn btn-primary" id="playerRetry" type="button">Retry</button><a class="btn btn-secondary" href="/details.php?id=${encodeURIComponent(root.dataset.id)}">Back to details</a></div>`;
    $('playerRetry')?.addEventListener('click', () => loadPlayback({ position: video.currentTime || info?.startSeconds || 0, autoplay: true }));
    showChrome(true);
  };

  const syncControls = () => {
    const paused = video.paused || video.ended;
    if (playPause) {
      playPause.innerHTML = Schofestream.icon(paused ? 'play' : 'pause');
      playPause.setAttribute('aria-label', paused ? 'Play' : 'Pause');
    }
    if (centerToggle) {
      centerToggle.innerHTML = Schofestream.icon(paused ? 'play' : 'pause');
      centerToggle.setAttribute('aria-label', paused ? 'Play' : 'Pause');
      centerToggle.classList.toggle('is-playing', !paused);
    }
    if (muteButton) {
      muteButton.innerHTML = Schofestream.icon(video.muted || video.volume === 0 ? 'muted' : 'volume');
      muteButton.setAttribute('aria-label', video.muted ? 'Unmute' : 'Mute');
    }
    if (volumeBar) volumeBar.value = String(video.muted ? 0 : video.volume);
  };

  const syncTimeline = () => {
    const duration = Number.isFinite(video.duration) ? video.duration : 0;
    const position = Number.isFinite(video.currentTime) ? video.currentTime : 0;
    if (seekBar) {
      seekBar.value = duration ? String(Math.round((position / duration) * 1000)) : '0';
      seekBar.setAttribute('aria-valuetext', `${Schofestream.clock(position)} of ${Schofestream.clock(duration)}`);
    }
    if (currentTime) currentTime.textContent = Schofestream.clock(position);
    if (durationTime) durationTime.textContent = Schofestream.clock(duration);
  };

  const anyMenuOpen = () => [audioMenu, subtitleMenu, qualityMenu].some(menu => menu && !menu.hidden);

  const showChrome = (keep = false) => {
    root.classList.remove('chrome-hidden');
    clearTimeout(hideTimer);
    if (!keep && !video.paused && !video.ended && !anyMenuOpen()) {
      hideTimer = setTimeout(() => root.classList.add('chrome-hidden'), 2800);
    }
  };

  const closeMenus = () => {
    [audioMenu, subtitleMenu, qualityMenu].forEach(menu => { if (menu) menu.hidden = true; });
    [audioButton, subtitleButton, qualityButton].forEach(button => button?.setAttribute('aria-expanded', 'false'));
    showChrome();
  };

  const toggleMenu = (menu, button) => {
    if (!menu || !button) return;
    const open = menu.hidden;
    closeMenus();
    menu.hidden = !open;
    button.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (open) {
      showChrome(true);
      menu.querySelector('button')?.focus();
    }
  };

  const optionButton = (label, value, selected) => `<button type="button" role="menuitemradio" aria-checked="${selected ? 'true' : 'false'}" data-value="${Schofestream.esc(String(value))}" class="player-menu-item${selected ? ' selected' : ''}"><span>${Schofestream.esc(label)}</span>${selected ? `<strong class="menu-check">${Schofestream.icon('check')}</strong>` : ''}</button>`;

  const renderMenus = () => {
    if (!info) return;
    if (audioMenu) {
      audioMenu.innerHTML = (info.audioTracks || []).length
        ? info.audioTracks.map(track => optionButton(`${track.label}${track.codec ? ` · ${track.codec}` : ''}`, track.index, Number(track.index) === Number(info.selectedAudio))).join('')
        : '<div class="player-menu-empty">No alternate audio tracks</div>';
    }
    if (subtitleMenu) {
      subtitleMenu.innerHTML = optionButton('Off', -1, Number(info.selectedSubtitle) < 0) + (info.subtitleTracks || []).map(track => optionButton(`${track.label}${track.forced ? ' · Forced' : ''}`, track.index, Number(track.index) === Number(info.selectedSubtitle))).join('');
    }
    if (qualityMenu) {
      const seen = new Set();
      const options = (info.qualityOptions || []).filter(option => {
        const key = Number(option.bitrate);
        if (!key || seen.has(key)) return false;
        seen.add(key);
        return true;
      });
      qualityMenu.innerHTML = options.map(option => optionButton(`${option.label} · ${(Number(option.bitrate) / 1_000_000).toFixed(1)} Mbps`, option.bitrate, Number(option.bitrate) === Number(info.selectedBitrate))).join('');
    }
    const selectedAudio = (info.audioTracks || []).find(track => Number(track.index) === Number(info.selectedAudio));
    if (audioButton) audioButton.textContent = selectedAudio?.language ? selectedAudio.language.toUpperCase() : 'Audio';
    if (subtitleButton) subtitleButton.textContent = Number(info.selectedSubtitle) >= 0 ? 'CC On' : 'CC';
    if (qualityButton) {
      const best = Number(info.qualityOptions?.[0]?.bitrate || 0);
      qualityButton.textContent = Number(info.selectedBitrate) === best ? 'Auto' : `${(Number(info.selectedBitrate) / 1_000_000).toFixed(0)}M`;
    }
  };

  const destroyStream = () => {
    try { hls?.destroy(); } catch (_) {}
    hls = null;
    video.pause();
    video.removeAttribute('src');
    video.load();
  };

  const seekWhenReady = start => {
    const target = Number(start) || 0;
    if (target <= 1) return;
    if (Number.isFinite(video.duration) && target < video.duration - 2) {
      try { video.currentTime = target; } catch (_) {}
    }
  };

  const streamReady = async (start, autoplay) => {
    seekWhenReady(start);
    loading.hidden = true;
    buffering.hidden = true;
    errorBox.hidden = true;
    failed = false;
    switching = false;
    syncControls();
    syncTimeline();
    showChrome();
    if (autoplay) {
      try { await video.play(); } catch (_) { showChrome(true); }
    }
  };

  const beginNativeHls = (start, autoplay) => {
    video.src = info.streamUrl;
    video.addEventListener('loadedmetadata', () => streamReady(start, autoplay), { once: true });
    video.addEventListener('error', () => fail('The browser could not load the stream from Jellyfin.', 'Check the Jellyfin transcoding log.'), { once: true });
  };

  const beginHlsJs = (start, autoplay) => {
    networkRetries = 0;
    mediaRetries = 0;
    hls = new Hls({
      enableWorker: true,
      backBufferLength: 90,
      maxBufferLength: 35,
      maxMaxBufferLength: 60,
      manifestLoadingMaxRetry: 3,
      levelLoadingMaxRetry: 3,
      fragLoadingMaxRetry: 3,
    });
    hls.loadSource(info.streamUrl);
    hls.attachMedia(video);
    hls.on(Hls.Events.MANIFEST_PARSED, () => streamReady(start, autoplay));
    hls.on(Hls.Events.ERROR, (_event, data) => {
      if (!data?.fatal) return;
      if (data.type === Hls.ErrorTypes.NETWORK_ERROR && networkRetries < 3) {
        networkRetries += 1;
        buffering.hidden = false;
        setTimeout(() => hls?.startLoad(), 500 * (2 ** (networkRetries - 1)));
        return;
      }
      if (data.type === Hls.ErrorTypes.MEDIA_ERROR && mediaRetries < 2) {
        mediaRetries += 1;
        hls?.recoverMediaError();
        return;
      }
      const detail = data?.details || data?.response?.code || 'fatal HLS error';
      destroyStream();
      fail(data.type === Hls.ErrorTypes.NETWORK_ERROR ? 'The stream connection was interrupted.' : 'The browser could not decode the video stream.', String(detail));
    });
  };

  const loadPlayback = async ({ position = null, autoplay = true, audio = undefined, subtitle = undefined, bitrate = undefined } = {}) => {
    if (switching) return;
    switching = true;
    closeMenus();
    playbackController?.abort();
    playbackController = new AbortController();

    const oldInfo = info;
    const oldPosition = position == null ? (Number.isFinite(video.currentTime) ? video.currentTime : 0) : Number(position);
    if (oldInfo) await report('stop');

    destroyStream();
    loading.hidden = false;
    buffering.hidden = true;
    errorBox.hidden = true;
    failed = false;

    if (audio !== undefined) preferences.audio = audio;
    if (subtitle !== undefined) preferences.subtitle = subtitle;
    if (bitrate !== undefined) preferences.bitrate = bitrate;

    const params = new URLSearchParams({ id: root.dataset.id });
    if (oldPosition > 0) params.set('position', String(oldPosition));
    if (preferences.audio !== null && preferences.audio !== undefined) params.set('audio', String(preferences.audio));
    if (preferences.subtitle !== null && preferences.subtitle !== undefined) params.set('subtitle', String(preferences.subtitle));
    if (preferences.bitrate > 0) params.set('bitrate', String(preferences.bitrate));

    try {
      info = await Schofestream.api(`/api/playback.php?${params.toString()}`, { signal: playbackController.signal });
      started = false;
      preferences.audio = info.selectedAudio;
      preferences.subtitle = info.selectedSubtitle;
      preferences.bitrate = info.selectedBitrate;
      title.textContent = info.item.seriesName ? `${info.item.seriesName} — ${info.item.name}` : info.item.name;
      document.title = `${info.item.name} · Schofestream`;
      renderMenus();

      if (info.item.backdrop) video.poster = info.item.backdrop;

      if (!nextPromise && info.item.type === 'Episode') {
        nextPromise = Schofestream.api(`/api/next-episode.php?id=${encodeURIComponent(root.dataset.id)}`)
          .then(data => (nextEpisode = data.next || null))
          .catch(() => (nextEpisode = null));
      }

      const start = oldPosition > 0 ? oldPosition : Number(info.startSeconds || 0);
      if (video.canPlayType('application/vnd.apple.mpegurl')) beginNativeHls(start, autoplay);
      else if (window.Hls && Hls.isSupported()) beginHlsJs(start, autoplay);
      else fail('This browser does not support HLS playback.');
    } catch (err) {
      if (err?.name === 'AbortError') return;
      switching = false;
      fail(err?.message || 'The playback request failed.');
    }
  };

  const switchStream = async (kind, value) => {
    const wasPlaying = !video.paused && !video.ended;
    const position = Number.isFinite(video.currentTime) ? video.currentTime : 0;
    if (kind === 'audio') await loadPlayback({ position, autoplay: wasPlaying, audio: Number(value) });
    if (kind === 'subtitle') await loadPlayback({ position, autoplay: wasPlaying, subtitle: Number(value) });
    if (kind === 'bitrate') await loadPlayback({ position, autoplay: wasPlaying, bitrate: Number(value) });
  };

  audioButton?.addEventListener('click', event => { event.stopPropagation(); toggleMenu(audioMenu, audioButton); });
  subtitleButton?.addEventListener('click', event => { event.stopPropagation(); toggleMenu(subtitleMenu, subtitleButton); });
  qualityButton?.addEventListener('click', event => { event.stopPropagation(); toggleMenu(qualityMenu, qualityButton); });
  audioMenu?.addEventListener('click', event => { const button = event.target.closest('[data-value]'); if (button) switchStream('audio', button.dataset.value); });
  subtitleMenu?.addEventListener('click', event => { const button = event.target.closest('[data-value]'); if (button) switchStream('subtitle', button.dataset.value); });
  qualityMenu?.addEventListener('click', event => { const button = event.target.closest('[data-value]'); if (button) switchStream('bitrate', button.dataset.value); });
  document.addEventListener('click', event => { if (!event.target.closest('.player-menu') && !event.target.closest('.player-options')) closeMenus(); });

  const togglePlayback = () => video.paused ? video.play().catch(() => {}) : video.pause();
  playPause?.addEventListener('click', togglePlayback);
  centerToggle?.addEventListener('click', togglePlayback);
  video.addEventListener('click', togglePlayback);
  skipBack?.addEventListener('click', () => { video.currentTime = Math.max(0, video.currentTime - 10); });
  skipForward?.addEventListener('click', () => { video.currentTime = Math.min(video.duration || Infinity, video.currentTime + 10); });
  seekBar?.addEventListener('input', () => {
    if (!Number.isFinite(video.duration) || !video.duration) return;
    video.currentTime = (Number(seekBar.value) / 1000) * video.duration;
  });
  seekBar?.addEventListener('change', () => report('progress'));
  volumeBar?.addEventListener('input', () => {
    video.muted = false;
    video.volume = Number(volumeBar.value);
    syncControls();
  });
  muteButton?.addEventListener('click', () => { video.muted = !video.muted; syncControls(); });
  fullscreenButton?.addEventListener('click', async () => {
    try {
      if (video.webkitEnterFullscreen && !document.fullscreenEnabled) {
        video.webkitEnterFullscreen();
      } else if (!document.fullscreenElement) {
        await $('videoWrap')?.requestFullscreen();
      } else {
        await document.exitFullscreen();
      }
    } catch (_) {}
  });

  const startNextCountdown = (autoplay = true) => {
    if (!nextEpisode || !nextBox) return;
    countdownValue = 10;
    nextBox.hidden = false;
    const countdownWrap = nextCountdown?.closest('.next-countdown');
    if (countdownWrap) countdownWrap.hidden = !autoplay;
    if (nextTitle) nextTitle.textContent = nextEpisode.name || 'Next episode';
    const ep = Schofestream.episodeLabel(nextEpisode);
    if (nextMeta) nextMeta.textContent = [nextEpisode.seriesName, ep].filter(Boolean).join(' · ');
    if (nextCountdown) nextCountdown.textContent = String(countdownValue);
    clearInterval(countdownTimer);
    if (!autoplay) { showChrome(true); return; }
    countdownTimer = setInterval(() => {
      countdownValue -= 1;
      if (nextCountdown) nextCountdown.textContent = String(Math.max(0, countdownValue));
      if (countdownValue <= 0) {
        clearInterval(countdownTimer);
        location.href = `/watch.php?id=${encodeURIComponent(nextEpisode.id)}`;
      }
    }, 1000);
  };

  playNext?.addEventListener('click', () => { if (nextEpisode) location.href = `/watch.php?id=${encodeURIComponent(nextEpisode.id)}`; });
  cancelNext?.addEventListener('click', () => { clearInterval(countdownTimer); if (nextBox) nextBox.hidden = true; showChrome(true); });

  root.addEventListener('mousemove', () => showChrome());
  root.addEventListener('touchstart', () => showChrome(), { passive: true });
  root.addEventListener('focusin', () => showChrome(true));

  document.addEventListener('keydown', e => {
    if (['INPUT','TEXTAREA','SELECT'].includes(document.activeElement?.tagName || '')) return;
    if (e.key === 'Escape' && anyMenuOpen()) { closeMenus(); return; }
    if (e.code === 'Space' || e.key.toLowerCase() === 'k') { e.preventDefault(); togglePlayback(); }
    if (e.key === 'ArrowLeft' || e.key.toLowerCase() === 'j') video.currentTime = Math.max(0, video.currentTime - 10);
    if (e.key === 'ArrowRight' || e.key.toLowerCase() === 'l') video.currentTime = Math.min(video.duration || Infinity, video.currentTime + 10);
    if (e.key.toLowerCase() === 'm') { video.muted = !video.muted; syncControls(); }
    if (e.key.toLowerCase() === 'f') fullscreenButton?.click();
    showChrome();
  });

  video.addEventListener('play', () => {
    syncControls();
    buffering.hidden = true;
    showChrome();
    if (!started) { started = true; report('start'); }
    else report('progress');
  });
  video.addEventListener('playing', () => { buffering.hidden = true; loading.hidden = true; });
  video.addEventListener('waiting', () => { if (!failed && !switching) buffering.hidden = false; });
  video.addEventListener('stalled', () => { if (!failed && !switching) buffering.hidden = false; });
  video.addEventListener('canplay', () => { buffering.hidden = true; });
  video.addEventListener('pause', () => { syncControls(); showChrome(true); if (!switching) report('progress'); });
  video.addEventListener('volumechange', syncControls);
  video.addEventListener('loadedmetadata', syncTimeline);
  video.addEventListener('durationchange', syncTimeline);
  video.addEventListener('seeked', () => { if (!switching) report('progress'); });
  video.addEventListener('timeupdate', () => {
    syncTimeline();
    const now = Date.now();
    if (!video.paused && now - lastReport > 10000) {
      lastReport = now;
      report('progress');
    }
  });
  video.addEventListener('ended', async () => {
    syncControls();
    await report('stop');
    if (nextPromise) await nextPromise;
    if (nextEpisode) startNextCountdown(info?.preferences?.autoplayNext !== false);
    else showChrome(true);
  });

  document.addEventListener('visibilitychange', () => { if (document.hidden && info) report('progress', true); });
  window.addEventListener('pagehide', () => report('stop', true));

  await loadPlayback({ autoplay: true });
})();

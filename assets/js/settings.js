(async () => {
  const root = document.getElementById('settingsApp');
  if (!root) return;

  const rowLabels = {
    continueWatching: 'Continue Watching', nextUp: 'Next Up', becauseYouWatched: 'Because You Watched',
    recentlyWatched: 'Recently Watched', favorites: 'My List', recentMovies: 'Recently Added Movies',
    recentShows: 'Recently Added TV', genres: 'Genres', collections: 'Collections'
  };
  let prefs;

  const rowItem = id => `<li class="home-row-setting" data-row="${Schofestream.esc(id)}">
    <span class="row-grip" aria-hidden="true">${Schofestream.icon('grip')}</span><strong>${Schofestream.esc(rowLabels[id] || id)}</strong>
    <label class="row-visible"><input type="checkbox" data-row-visible="${Schofestream.esc(id)}" ${prefs.hidden_home_rows?.includes(id) ? '' : 'checked'}><span>Show</span></label>
    <span class="row-order-controls"><button type="button" data-move="up" aria-label="Move ${Schofestream.esc(rowLabels[id] || id)} up">${Schofestream.icon('chevron-up')}</button><button type="button" data-move="down" aria-label="Move ${Schofestream.esc(rowLabels[id] || id)} down">${Schofestream.icon('chevron-down')}</button></span>
  </li>`;

  const render = () => {
    root.innerHTML = `<div class="page-heading"><div><div class="eyebrow">PERSONALISE SCHOFESTREAM</div><h1>Settings</h1><p class="muted">These settings belong to your Schofestream profile.</p></div></div>
      <form id="settingsForm" class="settings-stack">
        <section class="settings-card"><h2>Playback</h2>
          <label class="setting-field"><span><strong>Preferred quality</strong><small>Used automatically when a video starts.</small></span><select name="preferred_quality">
            <option value="0">Auto / Best</option><option value="12000000">Up to 12 Mbps</option><option value="8000000">Up to 8 Mbps</option><option value="5000000">Up to 5 Mbps</option><option value="2500000">Data saver · 2.5 Mbps</option>
          </select></label>
          <label class="setting-toggle"><span><strong>Autoplay next episode</strong><small>Start the next episode after the countdown.</small></span><input name="autoplay_next" type="checkbox"><span class="toggle-ui"></span></label>
          <label class="setting-field"><span><strong>Subtitles on start</strong><small>Choose whether Schofestream asks Jellyfin for its default subtitle track.</small></span><select name="default_subtitles"><option value="off">Off</option><option value="default">Jellyfin default</option></select></label>
        </section>
        <section class="settings-card"><h2>Home screen</h2><div class="setting-block setting-block-first"><strong>Rows</strong><small>Show, hide and arrange your Home screen.</small><ol id="homeRowOrder" class="home-row-order">${prefs.home_rows.map(rowItem).join('')}</ol></div></section>
        <div class="settings-actions"><button class="btn btn-primary" type="submit">Save settings</button><button class="btn btn-secondary" id="resetSettings" type="button">Reset defaults</button><span id="settingsStatus" class="muted" role="status"></span></div>
      </form>`;
    const form = document.getElementById('settingsForm');
    form.preferred_quality.value = String(prefs.preferred_quality || 0);
    form.autoplay_next.checked = !!prefs.autoplay_next;
    form.default_subtitles.value = prefs.default_subtitles || 'off';
  };

  const save = async form => {
    const payload = {
      preferred_quality: Number(form.preferred_quality.value),
      autoplay_next: form.autoplay_next.checked,
      default_subtitles: form.default_subtitles.value,
      home_rows: [...document.querySelectorAll('[data-row]')].map(el => el.dataset.row),
      hidden_home_rows: [...document.querySelectorAll('[data-row-visible]:not(:checked)')].map(el => el.dataset.rowVisible),
      csrf: Schofestream.csrf
    };
    const status = document.getElementById('settingsStatus');
    status.textContent = 'Saving…';
    const data = await Schofestream.api('/api/settings.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
    });
    prefs = data.preferences;
    status.textContent = 'Saved';
    Schofestream.toast('Preferences saved');
  };

  try {
    const data = await Schofestream.api('/api/settings.php');
    prefs = data.preferences;
    render();
  } catch (err) {
    Schofestream.showError(root, err.message);
    return;
  }

  root.addEventListener('click', async event => {
    const move = event.target.closest('[data-move]');
    if (move) {
      const item = move.closest('[data-row]');
      if (move.dataset.move === 'up' && item.previousElementSibling) item.parentNode.insertBefore(item, item.previousElementSibling);
      if (move.dataset.move === 'down' && item.nextElementSibling) item.parentNode.insertBefore(item.nextElementSibling, item);
      return;
    }

    if (event.target.closest('#resetSettings')) {
      try {
        const current = await Schofestream.api('/api/settings.php');
        const data = await Schofestream.api('/api/settings.php', {
          method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ ...current.defaults, csrf: Schofestream.csrf })
        });
        prefs = data.preferences;
        render();
        Schofestream.toast('Preferences reset');
      } catch (err) {
        Schofestream.toast(err.message);
      }
    }
  });

  root.addEventListener('submit', event => {
    if (event.target.id !== 'settingsForm') return;
    event.preventDefault();
    save(event.target).catch(err => Schofestream.toast(err.message));
  });
})();

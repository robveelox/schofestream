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
    <span class="row-grip" aria-hidden="true">☰</span><strong>${Schofestream.esc(rowLabels[id] || id)}</strong>
    <label class="row-visible"><input type="checkbox" data-row-visible="${Schofestream.esc(id)}" ${prefs.hidden_home_rows?.includes(id) ? '' : 'checked'}><span>Show</span></label>
    <span class="row-order-controls"><button type="button" data-move="up" aria-label="Move ${Schofestream.esc(rowLabels[id] || id)} up">↑</button><button type="button" data-move="down" aria-label="Move ${Schofestream.esc(rowLabels[id] || id)} down">↓</button></span>
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
        <section class="settings-card"><h2>Home screen</h2>
          <label class="setting-toggle"><span><strong>Recently Watched</strong><small>Show your recent viewing history on Home.</small></span><input name="show_recently_watched" type="checkbox"><span class="toggle-ui"></span></label>
          <label class="setting-toggle"><span><strong>Recommendations</strong><small>Show “Because You Watched” when Jellyfin has similar titles.</small></span><input name="show_recommendations" type="checkbox"><span class="toggle-ui"></span></label>
          <div class="setting-block"><strong>Row order</strong><small>Use the arrows to arrange your Home screen.</small><ol id="homeRowOrder" class="home-row-order">${prefs.home_rows.map(rowItem).join('')}</ol></div>
        </section>
        <div class="settings-actions"><button class="btn btn-primary" type="submit">Save settings</button><button class="btn btn-secondary" id="resetSettings" type="button">Reset defaults</button><span id="settingsStatus" class="muted" role="status"></span></div>
      </form>`;
    const form = document.getElementById('settingsForm');
    form.preferred_quality.value = String(prefs.preferred_quality || 0);
    form.autoplay_next.checked = !!prefs.autoplay_next;
    form.default_subtitles.value = prefs.default_subtitles || 'off';
    form.show_recently_watched.checked = !!prefs.show_recently_watched;
    form.show_recommendations.checked = !!prefs.show_recommendations;
  };

  const save = async (form) => {
    const rows = [...document.querySelectorAll('[data-row]')].map(el => el.dataset.row);
    const payload = {
      preferred_quality: Number(form.preferred_quality.value), autoplay_next: form.autoplay_next.checked,
      default_subtitles: form.default_subtitles.value, show_recently_watched: form.show_recently_watched.checked,
      show_recommendations: form.show_recommendations.checked, home_rows: rows,
      hidden_home_rows: [...document.querySelectorAll('[data-row-visible]:not(:checked)')].map(el => el.dataset.rowVisible), csrf: Schofestream.csrf
    };
    const status = document.getElementById('settingsStatus');
    status.textContent = 'Saving…';
    const data = await Schofestream.api('/api/settings.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
    prefs = data.preferences;
    status.textContent = 'Saved';
    Schofestream.toast('Preferences saved');
  };

  try {
    const data = await Schofestream.api('/api/settings.php');
    prefs = data.preferences;
    render();
  } catch (err) { Schofestream.showError(root, err.message); return; }

  root.addEventListener('click', async event => {
    const move = event.target.closest('[data-move]');
    if (move) {
      const li = move.closest('[data-row]');
      if (move.dataset.move === 'up' && li.previousElementSibling) li.parentNode.insertBefore(li, li.previousElementSibling);
      if (move.dataset.move === 'down' && li.nextElementSibling) li.parentNode.insertBefore(li.nextElementSibling, li);
      return;
    }
    if (event.target.closest('#resetSettings')) {
      try {
        const current = await Schofestream.api('/api/settings.php');
        const data = await Schofestream.api('/api/settings.php', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({...current.defaults,csrf:Schofestream.csrf})});
        prefs = data.preferences; render(); Schofestream.toast('Preferences reset');
      } catch (err) { Schofestream.toast(err.message); }
    }
  });
  root.addEventListener('submit', event => { if (event.target.id === 'settingsForm') { event.preventDefault(); save(event.target).catch(err => Schofestream.toast(err.message)); } });
})();

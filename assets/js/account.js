(async () => {
  const root = document.getElementById('accountApp'); if (!root) return;
  const date = value => value ? new Intl.DateTimeFormat(undefined,{dateStyle:'medium',timeStyle:'short'}).format(new Date(value)) : 'Not available';
  try {
    const data = await Schofestream.api('/api/profile.php'); const u = data.user;
    root.innerHTML = `<section class="profile-hero"><div class="profile-avatar"><img data-profile-avatar src="${Schofestream.esc(u.avatar)}" alt=""><span data-profile-fallback hidden>${Schofestream.esc((u.name||'S').slice(0,1).toUpperCase())}</span></div>
      <div><div class="eyebrow">YOUR PROFILE</div><h1>${Schofestream.esc(u.name)}</h1><p class="muted">Your Jellyfin identity, Schofestream preferences and recent activity.</p><div class="profile-actions"><a class="btn btn-primary" href="/settings.php">Preferences</a><a class="btn btn-secondary" href="/history.php">Watch history</a></div></div></section>
      <section class="stats-grid"><div><strong>${data.stats.favorites}</strong><span>My List titles</span></div><div><strong>${data.stats.recentMovies}</strong><span>Recent movie plays</span></div><div><strong>${data.stats.recentEpisodes}</strong><span>Recent episode plays</span></div></section>
      <div class="account-grid"><section class="settings-card"><h2>Jellyfin profile</h2><dl class="profile-dl"><div><dt>Username</dt><dd>${Schofestream.esc(u.name)}</dd></div><div><dt>Last activity</dt><dd>${Schofestream.esc(date(u.lastActivityDate))}</dd></div><div><dt>Audio language</dt><dd>${Schofestream.esc(u.audioLanguage || 'Jellyfin default')}</dd></div><div><dt>Subtitle language</dt><dd>${Schofestream.esc(u.subtitleLanguage || 'Jellyfin default')}</dd></div></dl></section>
      <section class="settings-card"><h2>This Schofestream session</h2><dl class="profile-dl"><div><dt>Device</dt><dd>Web Browser · ${Schofestream.esc(data.session.deviceId)}</dd></div><div><dt>Signed in</dt><dd>${Schofestream.esc(date(data.session.signedInAt))}</dd></div><div><dt>Version</dt><dd>0.3.0</dd></div></dl><a class="btn btn-secondary" href="/logout.php">Sign out of this device</a></section></div>
      ${Schofestream.row('Recently Watched', data.recent, '/history.php', {wide:true, direct:true})}`;
    const avatar=root.querySelector('[data-profile-avatar]'), fallback=root.querySelector('[data-profile-fallback]');
    avatar?.addEventListener('error',()=>{avatar.hidden=true;if(fallback)fallback.hidden=false},{once:true});
  } catch (err) { Schofestream.showError(root, err.message, {home:true}); }
})();

(async () => {
  const root = document.getElementById('profilesApp');
  if (!root) return;

  const selectProfile = async (profile, pin = '') => {
    try {
      const data = await Schofestream.api('/api/profile-select.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ profile_id: profile.id, pin, csrf: Schofestream.csrf })
      });
      window.location.href = data.redirect || '/';
    } catch (err) {
      if (err.status === 403 && profile.hasPin) {
        showPin(profile, err.message);
        return;
      }
      if (err.status === 409) {
        Schofestream.toast('This profile needs to be relinked in Manage Profiles.');
        return;
      }
      Schofestream.toast(err.message);
    }
  };

  const showPin = (profile, message = '') => {
    document.querySelector('.profile-pin-modal')?.remove();
    const modal = document.createElement('div');
    modal.className = 'profile-pin-modal';
    modal.innerHTML = `<div class="profile-pin-backdrop" data-pin-close></div><form class="profile-pin-card" data-pin-form>
      ${Schofestream.profileAvatar(profile.name, profile.avatar, 'profile-pin-avatar')}
      <h2>${Schofestream.esc(profile.name)}</h2><p>Enter the 4-digit profile PIN.</p>
      <input name="pin" type="password" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" autocomplete="off" aria-label="Profile PIN" required autofocus>
      <div class="form-error" data-pin-error ${message ? '' : 'hidden'}>${Schofestream.esc(message)}</div>
      <div class="profile-pin-actions"><button class="btn btn-primary" type="submit">Continue</button><button class="btn btn-secondary" type="button" data-pin-close>Cancel</button></div>
    </form>`;
    document.body.appendChild(modal);
    modal.querySelector('input')?.focus();
    modal.addEventListener('click', e => { if (e.target.closest('[data-pin-close]')) modal.remove(); });
    modal.querySelector('[data-pin-form]')?.addEventListener('submit', e => {
      e.preventDefault();
      const pin = new FormData(e.target).get('pin');
      modal.remove();
      selectProfile(profile, String(pin || ''));
    });
  };

  try {
    const data = await Schofestream.api('/api/household.php');
    const profiles = Array.isArray(data.profiles) ? data.profiles : [];
    root.innerHTML = `<div class="profiles-heading"><div class="eyebrow">${Schofestream.esc(data.name || 'YOUR HOUSEHOLD')}</div><h1>Who's watching?</h1><p>Choose a profile to keep viewing history, My List and Continue Watching separate.</p></div>
      <div class="watch-profile-grid">${profiles.map(profile => `<button class="watch-profile ${profile.needsRelink ? 'needs-relink' : ''}" type="button" data-profile-id="${Schofestream.esc(profile.id)}" ${profile.needsRelink ? 'aria-describedby="relink-' + Schofestream.esc(profile.id) + '"' : ''}>
        ${Schofestream.profileAvatar(profile.name, profile.avatar, 'watch-avatar')}
        <span class="watch-profile-name">${Schofestream.esc(profile.name)}</span>
        ${profile.hasPin ? '<span class="profile-lock">PIN</span>' : ''}
        ${profile.needsRelink ? `<span class="profile-relink" id="relink-${Schofestream.esc(profile.id)}">Relink required</span>` : ''}
      </button>`).join('')}</div>
      <div class="profiles-actions"><a class="btn btn-secondary" href="/manage-profiles.php">Manage profiles</a></div>`;

    root.addEventListener('click', e => {
      const button = e.target.closest('[data-profile-id]');
      if (!button) return;
      const profile = profiles.find(p => p.id === button.dataset.profileId);
      if (!profile) return;
      if (profile.needsRelink) { window.location.href = '/manage-profiles.php'; return; }
      if (profile.hasPin) showPin(profile); else selectProfile(profile);
    });
  } catch (err) {
    Schofestream.showError(root, err.message);
  }
})();

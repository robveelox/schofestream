(async () => {
  const root = document.getElementById('manageProfilesApp');
  if (!root) return;
  let state = null;

  const avatarPicker = (selected = 'ocean') => `<div class="avatar-picker" role="radiogroup" aria-label="Profile avatar">${(state?.avatarKeys || []).map(key => `<label class="avatar-choice"><input type="radio" name="avatar" value="${Schofestream.esc(key)}" ${key === selected ? 'checked' : ''}><span>${Schofestream.profileAvatar('S', key, 'avatar-choice-art')}</span></label>`).join('')}</div>`;

  const load = async () => {
    state = await Schofestream.api('/api/household.php');
    renderList();
  };

  const renderList = () => {
    const profiles = Array.isArray(state.profiles) ? state.profiles : [];
    root.innerHTML = `<div class="manage-profiles-heading"><div><div class="eyebrow">HOUSEHOLD PROFILES</div><h1>Manage profiles</h1><p>Each Schofestream profile links to one Jellyfin user so progress and favourites remain separate.</p></div>${profiles.length < state.maxProfiles ? '<button class="btn btn-primary" type="button" data-add-profile>Add profile</button>' : ''}</div>
      <div class="manage-profile-grid">${profiles.map(profile => `<article class="manage-profile-card">
        ${Schofestream.profileAvatar(profile.name, profile.avatar, 'manage-profile-avatar')}
        <div class="manage-profile-copy"><h2>${Schofestream.esc(profile.name)}</h2><p>Jellyfin: ${Schofestream.esc(profile.jellyfinUsername || 'Not linked')}</p><div class="profile-tags">${profile.isOwner ? '<span>Household owner</span>' : '<span>Linked user</span>'}${profile.hasPin ? '<span>PIN protected</span>' : ''}${profile.needsRelink ? '<span class="tag-warning">Relink required</span>' : ''}</div></div>
        <button class="btn btn-secondary" type="button" data-edit-profile="${Schofestream.esc(profile.id)}">Edit</button>
      </article>`).join('')}</div>
      <section class="profile-editor" id="profileEditor" hidden></section>
      <div class="household-note"><strong>How separation works</strong><p>Schofestream only stores profile names, avatar choices, optional PIN hashes and encrypted Jellyfin access tokens. Passwords are never stored. Jellyfin continues to own each person's viewing history, My List and playback progress.</p></div>`;
  };

  const openCreate = () => {
    const editor = document.getElementById('profileEditor');
    editor.hidden = false;
    editor.innerHTML = `<form class="profile-editor-card" data-create-form>
      <div class="editor-heading"><div><div class="eyebrow">NEW PROFILE</div><h2>Add a watcher</h2></div><button class="icon-btn" type="button" data-close-editor aria-label="Close">${Schofestream.icon('close')}</button></div>
      <label>Profile name<input name="name" maxlength="28" required placeholder="e.g. Guest"></label>
      <div class="editor-field"><span>Avatar</span>${avatarPicker('violet')}</div>
      <label>Optional 4-digit PIN<input name="pin" type="password" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" autocomplete="new-password" placeholder="Leave blank for no PIN"></label>
      <div class="link-user-panel"><h3>Link a Jellyfin user</h3><p>Enter this Jellyfin user's credentials once. Schofestream keeps the access token encrypted and does not store the password.</p>
      <label>Jellyfin username<input name="jellyfin_username" autocomplete="off" required></label><label>Jellyfin password<input name="jellyfin_password" type="password" autocomplete="new-password"></label></div>
      <div class="form-error" data-editor-error hidden></div><div class="editor-actions"><button class="btn btn-primary" type="submit">Create profile</button><button class="btn btn-secondary" type="button" data-close-editor>Cancel</button></div>
    </form>`;
    editor.scrollIntoView({ behavior: 'smooth', block: 'start' });
  };

  const openEdit = profile => {
    const editor = document.getElementById('profileEditor');
    editor.hidden = false;
    editor.innerHTML = `<form class="profile-editor-card" data-edit-form data-profile-id="${Schofestream.esc(profile.id)}">
      <div class="editor-heading"><div><div class="eyebrow">EDIT PROFILE</div><h2>${Schofestream.esc(profile.name)}</h2></div><button class="icon-btn" type="button" data-close-editor aria-label="Close">${Schofestream.icon('close')}</button></div>
      <label>Profile name<input name="name" maxlength="28" required value="${Schofestream.esc(profile.name)}"></label>
      <div class="editor-field"><span>Avatar</span>${avatarPicker(profile.avatar)}</div>
      <label>${profile.hasPin ? 'Replace PIN' : 'Add optional PIN'}<input name="pin" type="password" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" autocomplete="new-password" placeholder="${profile.hasPin ? 'Leave blank to keep current PIN' : '4 digits'}"></label>
      ${profile.hasPin ? '<label class="check-row"><input name="clear_pin" type="checkbox"> Remove current PIN</label>' : ''}
      <div class="linked-identity"><strong>Jellyfin identity</strong><span>${Schofestream.esc(profile.jellyfinUsername)}</span>${profile.isOwner ? '<small>The owner identity changes when the household signs in.</small>' : `<small>${profile.needsRelink ? 'This link has expired. Enter credentials below to relink it.' : 'Only use Relink if you need to change or refresh the Jellyfin account.'}</small>`}</div>
      ${profile.isOwner ? '' : `<details class="relink-panel" ${profile.needsRelink ? 'open' : ''}><summary>Relink Jellyfin user</summary><label>Jellyfin username<input name="jellyfin_username" autocomplete="off" value="${Schofestream.esc(profile.jellyfinUsername)}"></label><label>Jellyfin password<input name="jellyfin_password" type="password" autocomplete="new-password"></label><button class="btn btn-secondary" type="button" data-relink>Relink user</button></details>`}
      <div class="form-error" data-editor-error hidden></div><div class="editor-actions"><button class="btn btn-primary" type="submit">Save profile</button><button class="btn btn-secondary" type="button" data-close-editor>Cancel</button>${profile.isOwner ? '' : '<button class="btn btn-danger" type="button" data-delete-profile>Delete profile</button>'}</div>
    </form>`;
    editor.scrollIntoView({ behavior: 'smooth', block: 'start' });
  };

  const errorFor = form => form.querySelector('[data-editor-error]');
  const showError = (form, err) => { const box = errorFor(form); if (!box) return; box.textContent = err.message; box.hidden = false; };
  const payloadFrom = form => ({
    name: form.elements.name?.value || '', avatar: form.querySelector('input[name="avatar"]:checked')?.value || 'ocean',
    pin: form.elements.pin?.value || '', clear_pin: !!form.elements.clear_pin?.checked, csrf: Schofestream.csrf
  });

  root.addEventListener('click', async e => {
    if (e.target.closest('[data-add-profile]')) return openCreate();
    if (e.target.closest('[data-close-editor]')) { const editor = document.getElementById('profileEditor'); if (editor) editor.hidden = true; return; }
    const edit = e.target.closest('[data-edit-profile]');
    if (edit) { const p = state.profiles.find(x => x.id === edit.dataset.editProfile); if (p) openEdit(p); return; }

    const relink = e.target.closest('[data-relink]');
    if (relink) {
      const form = relink.closest('form');
      const payload = { action: 'relink', profile_id: form.dataset.profileId, jellyfin_username: form.elements.jellyfin_username.value, jellyfin_password: form.elements.jellyfin_password.value, csrf: Schofestream.csrf };
      try { await Schofestream.api('/api/household.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)}); Schofestream.toast('Jellyfin user relinked'); await load(); }
      catch (err) { showError(form, err); }
      return;
    }

    const del = e.target.closest('[data-delete-profile]');
    if (del) {
      const form = del.closest('form');
      const profile = state.profiles.find(x => x.id === form.dataset.profileId);
      if (!profile || !confirm(`Delete the Schofestream profile “${profile.name}”? The Jellyfin user itself will not be deleted.`)) return;
      try { await Schofestream.api('/api/household.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'delete',profile_id:profile.id,csrf:Schofestream.csrf})}); Schofestream.toast('Profile removed'); await load(); }
      catch (err) { showError(form, err); }
    }
  });

  root.addEventListener('submit', async e => {
    const form = e.target;
    if (!form.matches('[data-create-form],[data-edit-form]')) return;
    e.preventDefault();
    const button = form.querySelector('button[type="submit"]');
    button.disabled = true;
    try {
      if (form.matches('[data-create-form]')) {
        const payload = { action:'create', ...payloadFrom(form), jellyfin_username: form.elements.jellyfin_username.value, jellyfin_password: form.elements.jellyfin_password.value };
        await Schofestream.api('/api/household.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
        Schofestream.toast('Profile created');
      } else {
        const payload = { action:'update', profile_id:form.dataset.profileId, ...payloadFrom(form) };
        await Schofestream.api('/api/household.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
        Schofestream.toast('Profile saved');
      }
      await load();
    } catch (err) { showError(form, err); button.disabled = false; }
  });

  try { await load(); } catch (err) { Schofestream.showError(root, err.message); }
})();

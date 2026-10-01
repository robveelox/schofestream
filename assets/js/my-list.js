(() => {
  const grid = document.getElementById('myListGrid');
  const sort = document.getElementById('myListSort');
  const count = document.getElementById('myListCount');
  if (!grid) return;

  let controller = null;

  const empty = () => '<div class="empty-state empty-card"><strong>Your list is empty.</strong><span>Open any movie or series and choose “+ My List”.</span><a class="btn btn-secondary" href="/">Browse Schofestream</a></div>';
  const renderItem = item => `<div class="my-list-item" data-id="${Schofestream.esc(item.id)}">${Schofestream.card(item)}<button class="my-list-remove" type="button" data-remove-id="${Schofestream.esc(item.id)}" aria-label="Remove ${Schofestream.esc(item.name)} from My List">×</button></div>`;

  const updateCount = () => {
    const total = grid.querySelectorAll('.my-list-item').length;
    if (count) count.textContent = `${total} saved title${total === 1 ? '' : 's'}`;
    if (!total) grid.innerHTML = empty();
  };

  const load = async () => {
    controller?.abort();
    controller = new AbortController();
    grid.innerHTML = '<div class="loading-state"><span class="spinner"></span> Loading your list…</div>';
    try {
      const data = await Schofestream.api(`/api/my-list.php?sort=${encodeURIComponent(sort?.value || 'title')}`, { signal: controller.signal });
      grid.innerHTML = data.items?.length ? data.items.map(renderItem).join('') : empty();
      if (count) count.textContent = `${data.items?.length || 0} saved title${(data.items?.length || 0) === 1 ? '' : 's'}`;
    } catch (err) {
      if (err?.name !== 'AbortError') Schofestream.showError(grid, err.message);
    }
  };

  grid.addEventListener('click', async event => {
    const button = event.target.closest('[data-remove-id]');
    if (!button) return;
    event.preventDefault();
    event.stopPropagation();
    const id = button.dataset.removeId;
    const item = button.closest('.my-list-item');
    button.disabled = true;
    try {
      await Schofestream.setFavorite(id, false);
      item?.classList.add('removing');
      setTimeout(() => { item?.remove(); updateCount(); }, 180);
      Schofestream.toast('Removed from My List');
    } catch (err) {
      button.disabled = false;
      Schofestream.toast(err.message);
    }
  });

  sort?.addEventListener('change', load);
  load();
})();

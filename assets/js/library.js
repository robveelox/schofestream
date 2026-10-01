(() => {
  const root = document.getElementById('libraryApp');
  const grid = document.getElementById('libraryGrid');
  const sort = document.getElementById('librarySort');
  const count = document.getElementById('libraryCount');
  if (!root || !grid) return;
  let controller = null;

  const load = async () => {
    controller?.abort();
    controller = new AbortController();
    grid.innerHTML = '<div class="loading-state"><span class="spinner"></span> Loading library…</div>';
    try {
      const data = await Schofestream.api(`/api/library.php?type=${encodeURIComponent(root.dataset.type)}&sort=${encodeURIComponent(sort?.value || 'title')}`, { signal: controller.signal });
      const total = data.total || data.items?.length || 0;
      if (count) count.textContent = `${total} title${total === 1 ? '' : 's'}`;
      grid.innerHTML = data.items?.length ? data.items.map(i => Schofestream.card(i)).join('') : '<div class="empty-state">Nothing here yet.</div>';
    } catch (err) {
      if (err?.name !== 'AbortError') Schofestream.showError(grid, err.message);
    }
  };
  sort?.addEventListener('change', load);
  load();
})();

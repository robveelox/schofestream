(async () => {
  const grid = document.getElementById('collectionsGrid');
  if (!grid) return;
  try {
    const data = await Schofestream.api('/api/collections.php');
    grid.innerHTML = data.items.length ? data.items.map(i => Schofestream.card(i)).join('') : '<div class="empty-state">No collections are configured in Jellyfin yet.</div>';
  } catch (err) {
    Schofestream.showError(grid, err.message);
  }
})();

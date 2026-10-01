(async () => {
  const genresRoot = document.getElementById('genresApp');
  const genreRoot = document.getElementById('genreApp');
  try {
    if (genresRoot) {
      const grid = document.getElementById('genreGrid');
      const data = await Schofestream.api('/api/genres.php');
      grid.innerHTML = data.genres.length ? data.genres.map((g, index) => `<a class="genre-tile genre-${index % 6}" href="/genre.php?name=${encodeURIComponent(g.name)}"><span>${Schofestream.esc(g.name)}</span><small>Explore →</small></a>`).join('') : '<div class="empty-state">No genres found.</div>';
    }
    if (genreRoot) {
      const grid = document.getElementById('genreItems');
      const data = await Schofestream.api(`/api/genres.php?name=${encodeURIComponent(genreRoot.dataset.name)}`);
      grid.innerHTML = data.items.length ? data.items.map(i => Schofestream.card(i)).join('') : '<div class="empty-state">No titles in this genre.</div>';
    }
  } catch (err) {
    const target = document.getElementById('genreGrid') || document.getElementById('genreItems') || genresRoot || genreRoot;
    if (target) Schofestream.showError(target, err.message);
  }
})();

(async () => {
  const root = document.getElementById('searchApp');
  const results = document.getElementById('searchResults');
  if (!root || !results) return;
  const query = (root.dataset.query || '').trim();
  if (query.length < 2) return;

  const group = (title, items, options = {}) => items?.length ? `<section class="search-group"><div class="row-heading"><h2>${Schofestream.esc(title)}</h2><span class="result-count">${items.length}</span></div><div class="poster-grid">${items.map(i => Schofestream.card(i, options)).join('')}</div></section>` : '';
  try {
    const data = await Schofestream.api(`/api/search.php?q=${encodeURIComponent(query)}`);
    const people = data.people?.length ? `<section class="search-group"><div class="row-heading"><h2>People</h2><span class="result-count">${data.people.length}</span></div><div class="people-search-grid">${data.people.map(p => `<a class="person-search-card" href="/person.php?name=${encodeURIComponent(p.name)}"><div>${Schofestream.art(p.image || '')}</div><strong>${Schofestream.esc(p.name)}</strong></a>`).join('')}</div></section>` : '';
    const html = `${group('Movies', data.movies)}${group('TV Shows', data.shows)}${group('Episodes', data.episodes, { wide: true, direct: true })}${people}`;
    results.innerHTML = html || `<div class="empty-state"><strong>No results for “${Schofestream.esc(query)}”.</strong><span>Try a shorter title, actor name or different spelling.</span></div>`;
  } catch (err) {
    Schofestream.showError(results, err.message);
  }
})();

(async () => {
  const root = document.getElementById('personApp');
  if (!root) return;
  try {
    const data = await Schofestream.api(`/api/person.php?name=${encodeURIComponent(root.dataset.name)}`);
    const p = data.person || {};
    document.title = `${p.name || root.dataset.name} · Schofestream`;
    const overview = p.overview ? `<p class="person-overview">${Schofestream.esc(p.overview)}</p>` : '<p class="muted">Titles in your library featuring this person.</p>';
    root.innerHTML = `<section class="person-hero"><div class="person-photo">${Schofestream.art(p.image || '')}</div><div><div class="eyebrow">PERSON</div><h1>${Schofestream.esc(p.name || root.dataset.name)}</h1>${overview}</div></section><section class="person-titles"><div class="row-heading"><h2>On Schofestream</h2></div><div class="poster-grid">${data.items?.length ? data.items.map(i => Schofestream.card(i)).join('') : '<div class="empty-state">No matching titles found in your library.</div>'}</div></section>`;
  } catch (err) {
    Schofestream.showError(root, err.message, { home: true });
  }
})();

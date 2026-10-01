(async () => {
  const root = document.getElementById('homeApp');
  if (!root) return;
  const rows = root.querySelector('.home-rows');
  if (rows) rows.innerHTML = Schofestream.skeletonRows(4);

  try {
    const data = await Schofestream.api('/api/home.php');
    const hero = data.hero;
    const heroPlay = hero
      ? (hero.type === 'Movie' ? `/watch.php?id=${encodeURIComponent(hero.id)}` : `/details.php?id=${encodeURIComponent(hero.id)}`)
      : '#';
    const heroHtml = hero ? `<section class="hero"${hero.backdrop ? ` style="background-image:linear-gradient(90deg,rgba(4,9,17,.98) 0%,rgba(4,9,17,.76) 38%,rgba(4,9,17,.12) 78%),linear-gradient(0deg,#07111f 0%,transparent 42%),url('${Schofestream.esc(hero.backdrop)}')"` : ''}>
      <div class="hero-content"><div class="eyebrow">FEATURED ON SCHOFESTREAM</div><h1>${Schofestream.esc(hero.name)}</h1>
      ${[hero.year, hero.officialRating, hero.runtimeSeconds ? Schofestream.duration(hero.runtimeSeconds) : ''].filter(Boolean).length ? `<div class="hero-meta">${[hero.year, hero.officialRating, hero.runtimeSeconds ? Schofestream.duration(hero.runtimeSeconds) : ''].filter(Boolean).map(Schofestream.esc).join('<span>•</span>')}</div>` : ''}
      <p>${Schofestream.esc(hero.overview || 'Ready when you are.')}</p>
      <div class="hero-actions"><a class="btn btn-primary" href="${heroPlay}">▶ ${hero.type === 'Movie' ? (hero.positionSeconds ? 'Resume' : 'Play') : 'View series'}</a><a class="btn btn-glass" href="/details.php?id=${encodeURIComponent(hero.id)}">ⓘ More info</a></div>
      </div></section>` : '<section class="hero empty-hero"><div class="hero-content"><h1>Welcome to Schofestream</h1><p>Add something to your Jellyfin library and it’ll appear here.</p></div></section>';

    const genreChips = (data.genres || []).length ? `<section class="media-row genre-strip"><div class="row-heading"><h2>Browse genres</h2><a class="row-more" href="/genres.php">View all <span aria-hidden="true">→</span></a></div><div class="genre-chips">${data.genres.slice(0, 10).map(g => `<a href="/genre.php?name=${encodeURIComponent(g.name)}">${Schofestream.esc(g.name)}</a>`).join('')}</div></section>` : '';
    const genreRows = (data.genreRows || []).map(row => Schofestream.row(row.name, row.items, `/genre.php?name=${encodeURIComponent(row.name)}`)).join('');
    const partial = data.partial ? `<div class="home-notice" role="status">Some rows couldn’t be loaded right now. The rest of Schofestream is still available.</div>` : '';

    const rowHtml = [
      Schofestream.row('Continue Watching', data.continueWatching, '', { wide: true, direct: true }),
      Schofestream.row('Next Up', data.nextUp, '', { wide: true, direct: true }),
      Schofestream.row('My List', data.favorites, '/my-list.php'),
      Schofestream.row('Recently Added Movies', data.recentMovies, '/library.php?type=Movie'),
      Schofestream.row('Recently Added TV', data.recentShows, '/library.php?type=Series'),
      genreChips,
      genreRows,
      Schofestream.row('Collections', data.collections, '/collections.php'),
    ].join('');

    root.innerHTML = heroHtml + `<div class="content-shell home-rows">${partial}${rowHtml || '<div class="empty-state">Your library is ready, but there are no titles to show yet.</div>'}</div>`;
  } catch (err) {
    Schofestream.showError(root, err.message);
  }
})();

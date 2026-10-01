(async () => {
  const root = document.getElementById('detailsApp');
  if (!root) return;
  const App = Schofestream;

  const renderEpisodes = items => items.map(ep => {
    const label = ep.indexNumber != null ? `Episode ${ep.indexNumber}` : 'Episode';
    const resume = ep.positionSeconds ? 'Resume' : (ep.played ? 'Watch again' : 'Play');
    return `<a class="episode-card" href="/watch.php?id=${encodeURIComponent(ep.id)}" aria-label="${App.esc(`${label}: ${ep.name}`)}">
      <div class="episode-art">${App.art(ep.thumb || ep.backdrop || ep.poster)}<span class="episode-play" aria-hidden="true">${App.icon('play')}</span>${App.progress(ep)}${ep.played ? `<span class="watched-badge">${App.icon('check')} Watched</span>` : ''}</div>
      <div class="episode-copy"><div class="episode-top"><strong>${App.esc(label)} · ${App.esc(ep.name)}</strong><span>${ep.runtimeSeconds ? App.duration(ep.runtimeSeconds) : ''}</span></div><p>${App.esc(ep.overview || 'Description unavailable.')}</p><span class="episode-cta">${resume}</span></div>
    </a>`;
  }).join('');

  try {
    const data = await App.api(`/api/item.php?id=${encodeURIComponent(root.dataset.id)}`);
    const item = data.item || {};
    if (!item.id) throw new Error('This title could not be loaded.');
    document.title = `${item.name || 'Title'} · Schofestream`;

    const meta = [
      item.year,
      item.officialRating,
      item.runtimeSeconds ? App.duration(item.runtimeSeconds) : '',
      item.communityRating ? `Rating ${Number(item.communityRating).toFixed(1)}` : '',
    ].filter(Boolean);
    const actors = (item.people || []).filter(p => p.type === 'Actor');
    const directors = (item.people || []).filter(p => p.type === 'Director').map(p => p.name).filter(Boolean);
    const tagline = item.taglines?.find(Boolean) || '';
    const typeLabel = item.type === 'Series' ? 'TV SERIES' : (item.type === 'BoxSet' ? 'COLLECTION' : (item.type || 'TITLE').toUpperCase());

    let primaryAction = '';
    if (['Movie', 'Episode'].includes(item.type)) {
      primaryAction = `<a class="btn btn-primary" href="/watch.php?id=${encodeURIComponent(item.id)}">${App.icon('play')} ${item.positionSeconds ? 'Resume' : (item.played ? 'Watch again' : 'Play')}</a>`;
    } else if (item.type === 'Series' && data.seriesPlay?.id) {
      primaryAction = `<a class="btn btn-primary" href="/watch.php?id=${encodeURIComponent(data.seriesPlay.id)}">${App.icon('play')} ${data.seriesPlay.positionSeconds ? 'Resume series' : 'Start watching'}</a>`;
    }

    const genreLinks = (item.genres || []).slice(0, 8).map(g => `<a href="/genre.php?name=${encodeURIComponent(g)}">${App.esc(g)}</a>`).join('');
    const overviewLong = String(item.overview || '').length > 430;
    const overview = item.overview || 'No description is available for this title yet.';
    const warnings = (data.warnings || []).length ? `<div class="detail-warning" role="status">${data.warnings.map(App.esc).join(' ')}</div>` : '';

    const castVisible = actors.slice(0, 18);
    const castExtra = actors.slice(18);
    const castCard = (p, extra = false) => `<button class="cast-card cast-person-button${extra ? ' cast-extra' : ''}" type="button" data-person-name="${App.esc(p.name)}" aria-label="Open ${App.esc(p.name)} profile"${extra ? ' hidden' : ''}><div class="cast-photo">${p.image ? App.art(p.image) : `<span class="cast-placeholder" aria-hidden="true">${App.esc((p.name || '?').charAt(0).toUpperCase())}</span>`}</div><strong>${App.esc(p.name)}</strong><small>${App.esc(p.role || 'Cast')}</small></button>`;
    const castHtml = actors.length ? `<section class="detail-section cast-section"><div class="row-heading"><h2>Cast</h2>${castExtra.length ? '<button class="row-more button-link" id="toggleCast" type="button" aria-expanded="false">Show all</button>' : ''}</div><div class="cast-rail">${castVisible.map(p => castCard(p)).join('')}${castExtra.map(p => castCard(p, true)).join('')}</div></section>` : '';

    const seriesFact = item.type === 'Episode' && item.seriesId && item.seriesName
      ? `<div><span>Series</span><a href="/details.php?id=${encodeURIComponent(item.seriesId)}">${App.esc(item.seriesName)}</a></div>` : '';

    const seasonBlock = item.type === 'Series'
      ? `<section class="episodes-section"><div class="row-heading"><h2>Episodes</h2>${(data.seasons || []).length ? `<select id="seasonSelect" aria-label="Choose season">${data.seasons.map(s => `<option value="${App.esc(s.id)}">${App.esc(s.name)}</option>`).join('')}</select>` : ''}</div><div id="episodeList" class="episode-list">${(data.seasons || []).length ? '<div class="loading-state"><span class="spinner"></span> Loading episodes…</div>' : '<div class="empty-state">No seasons are available for this series.</div>'}</div></section>` : '';

    root.innerHTML = `${warnings}<section class="detail-hero"${item.backdrop ? ` style="background-image:linear-gradient(90deg,rgba(4,9,17,.99) 0%,rgba(4,9,17,.78) 42%,rgba(4,9,17,.18) 80%),linear-gradient(0deg,#07111f 0%,transparent 48%),url('${App.esc(item.backdrop)}')"` : ''}>
      <div class="detail-copy"><div class="eyebrow">${App.esc(typeLabel)}</div><h1>${App.esc(item.name)}</h1>
      ${tagline ? `<div class="detail-tagline">${App.esc(tagline)}</div>` : ''}
      ${meta.length ? `<div class="hero-meta">${meta.map(App.esc).join('<span>•</span>')}</div>` : ''}
      <div class="detail-overview-wrap"><p class="detail-overview${overviewLong ? ' is-clamped' : ''}" id="detailOverview">${App.esc(overview)}</p>${overviewLong ? '<button class="overview-toggle button-link" id="overviewToggle" type="button" aria-expanded="false">Read more</button>' : ''}</div>
      <div class="detail-actions">${primaryAction}<button class="btn btn-glass favorite-button" id="favoriteButton" type="button">${item.favorite ? 'In My List' : 'Add to My List'}</button>${['Movie','Episode'].includes(item.type) ? `<button class="btn btn-glass" id="watchedButton" type="button">${item.played ? 'Watched' : 'Mark watched'}</button>` : ''}</div>
      <div class="detail-facts">${seriesFact}${genreLinks ? `<div><span>Genres</span><div class="detail-links">${genreLinks}</div></div>` : ''}${directors.length ? `<div><span>Director${directors.length > 1 ? 's' : ''}</span>${App.esc(directors.join(', '))}</div>` : ''}${item.studios?.length ? `<div><span>Studio</span>${App.esc(item.studios.slice(0,3).join(', '))}</div>` : ''}</div>
      </div></section>
      ${seasonBlock}
      ${data.children?.length ? `<section class="detail-section content-shell">${App.row('Included in this collection', data.children)}</section>` : ''}
      ${castHtml}
      ${data.similar?.length ? `<section class="detail-section content-shell">${App.row('More like this', data.similar)}</section>` : ''}`;

    root.querySelectorAll('.cast-person-button[data-person-name]').forEach(button => {
      button.addEventListener('click', event => {
        event.preventDefault();
        event.stopPropagation();
        const name = button.dataset.personName || '';
        if (name) location.href = `/person.php?name=${encodeURIComponent(name)}`;
      });
    });

    document.getElementById('toggleCast')?.addEventListener('click', event => {
      const button = event.currentTarget;
      const expanded = button.getAttribute('aria-expanded') === 'true';
      root.querySelectorAll('.cast-extra').forEach(card => { card.hidden = expanded; });
      button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
      button.textContent = expanded ? 'Show all' : 'Show less';
    });

    document.getElementById('overviewToggle')?.addEventListener('click', event => {
      const button = event.currentTarget;
      const overviewNode = document.getElementById('detailOverview');
      const expanded = button.getAttribute('aria-expanded') === 'true';
      overviewNode?.classList.toggle('is-clamped', expanded);
      button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
      button.textContent = expanded ? 'Read more' : 'Show less';
    });

    let favorite = !!item.favorite;
    const favoriteButton = document.getElementById('favoriteButton');
    favoriteButton?.addEventListener('click', async () => {
      favoriteButton.disabled = true;
      const next = !favorite;
      try {
        await App.setFavorite(item.id, next);
        favorite = next;
        favoriteButton.textContent = favorite ? 'In My List' : 'Add to My List';
        App.toast(favorite ? 'Added to My List' : 'Removed from My List');
      } catch (err) {
        App.toast(err.message);
      } finally { favoriteButton.disabled = false; }
    });

    let watched = !!item.played;
    const watchedButton = document.getElementById('watchedButton');
    watchedButton?.addEventListener('click', async () => {
      watchedButton.disabled = true;
      const next = !watched;
      try {
        await App.setWatched(item.id, next);
        watched = next;
        watchedButton.textContent = watched ? 'Watched' : 'Mark watched';
        App.toast(watched ? 'Marked as watched' : 'Marked as unwatched');
      } catch (err) {
        App.toast(err.message);
      } finally { watchedButton.disabled = false; }
    });

    if (item.type === 'Series' && (data.seasons || []).length) {
      const select = document.getElementById('seasonSelect');
      const list = document.getElementById('episodeList');
      if (data.seriesPlay?.parentIndexNumber != null) {
        const preferred = data.seasons.find(s => Number(s.indexNumber) === Number(data.seriesPlay.parentIndexNumber));
        if (preferred && select) select.value = preferred.id;
      }
      let episodeController = null;
      const load = async () => {
        if (!select || !list) return;
        episodeController?.abort();
        episodeController = new AbortController();
        list.innerHTML = '<div class="loading-state"><span class="spinner"></span> Loading episodes…</div>';
        try {
          const eps = await App.api(`/api/episodes.php?seriesId=${encodeURIComponent(item.id)}&seasonId=${encodeURIComponent(select.value)}`, { signal: episodeController.signal });
          list.innerHTML = eps.items?.length ? renderEpisodes(eps.items) : '<div class="empty-state">No episodes in this season.</div>';
        } catch (err) {
          if (err?.name !== 'AbortError') App.showError(list, err.message);
        }
      };
      select?.addEventListener('change', load);
      load();
    }
  } catch (err) {
    if (err?.name !== 'AbortError') App.showError(root, err.message, { home: true });
  }
})();

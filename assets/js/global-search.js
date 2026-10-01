(() => {
  const overlay = document.getElementById('searchOverlay');
  const input = document.getElementById('instantSearchInput');
  const results = document.getElementById('instantSearchResults');
  if (!overlay || !input || !results || !window.Schofestream) return;

  const App = window.Schofestream;
  let timer = 0;
  let controller = null;
  let activeIndex = -1;
  let lastFocused = null;

  const resultLinks = () => [...results.querySelectorAll('a.instant-result, a.view-search-results')];
  const setActive = index => {
    const links = resultLinks();
    if (!links.length) return;
    activeIndex = (index + links.length) % links.length;
    links.forEach((link, i) => link.classList.toggle('keyboard-active', i === activeIndex));
    links[activeIndex]?.scrollIntoView({ block: 'nearest' });
  };

  const closeSearch = () => {
    controller?.abort();
    clearTimeout(timer);
    overlay.hidden = true;
    document.body.classList.remove('search-open');
    activeIndex = -1;
    if (lastFocused instanceof HTMLElement) lastFocused.focus();
  };

  const openSearch = () => {
    lastFocused = document.activeElement;
    overlay.hidden = false;
    document.body.classList.add('search-open');
    setTimeout(() => input.focus(), 20);
  };

  document.getElementById('openSearch')?.addEventListener('click', openSearch);
  document.getElementById('mobileSearch')?.addEventListener('click', openSearch);
  document.querySelectorAll('[data-search-close]').forEach(el => el.addEventListener('click', closeSearch));

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && !overlay.hidden) { closeSearch(); return; }
    if (e.key === '/' && overlay.hidden && !['INPUT','TEXTAREA','SELECT'].includes(document.activeElement?.tagName || '')) {
      e.preventDefault();
      openSearch();
    }
  });

  overlay.addEventListener('keydown', e => {
    if (e.key === 'ArrowDown') { e.preventDefault(); setActive(activeIndex + 1); }
    if (e.key === 'ArrowUp') { e.preventDefault(); setActive(activeIndex - 1); }
    if (e.key === 'Enter' && activeIndex >= 0) {
      const link = resultLinks()[activeIndex];
      if (link) { e.preventDefault(); link.click(); }
    }
  });

  const resultTile = item => `<a class="instant-result" href="${App.href(item, item.type === 'Episode')}">${App.art(item.poster || item.thumb || '')}<span><strong>${App.esc(item.type === 'Episode' && item.seriesName ? item.seriesName : item.name)}</strong><small>${App.esc(item.type === 'Episode' ? `${App.episodeLabel(item)} · ${item.name}` : item.type)}</small></span></a>`;
  const personTile = person => `<a class="instant-result person-result" href="/person.php?name=${encodeURIComponent(person.name)}">${App.art(person.image || '')}<span><strong>${App.esc(person.name)}</strong><small>Person</small></span></a>`;

  input.addEventListener('input', () => {
    clearTimeout(timer);
    controller?.abort();
    activeIndex = -1;
    const q = input.value.trim();
    if (q.length < 2) {
      results.innerHTML = '<div class="search-hint">Type at least 2 characters.</div>';
      return;
    }

    results.innerHTML = '<div class="search-hint"><span class="spinner"></span> Searching…</div>';
    timer = setTimeout(async () => {
      controller = new AbortController();
      try {
        const data = await App.api(`/api/search.php?compact=1&limit=5&q=${encodeURIComponent(q)}`, { signal: controller.signal });
        const groups = [
          ['Movies', data.movies || []],
          ['TV Shows', data.shows || []],
          ['Episodes', data.episodes || []],
        ].filter(([, items]) => items.length);
        const people = (data.people || []).slice(0, 4);
        const mediaHtml = groups.map(([label, items]) => `<div class="instant-group"><div class="instant-label">${App.esc(label)}</div>${items.slice(0, 5).map(resultTile).join('')}</div>`).join('');
        const peopleHtml = people.length ? `<div class="instant-group"><div class="instant-label">People</div>${people.map(personTile).join('')}</div>` : '';
        results.innerHTML = mediaHtml || peopleHtml
          ? `${mediaHtml}${peopleHtml}<a class="view-search-results" href="/search.php?q=${encodeURIComponent(q)}">View all results <span aria-hidden="true">→</span></a>`
          : `<div class="search-hint"><strong>No matches for “${App.esc(q)}”</strong><span>Try another title, actor or episode.</span></div>`;
      } catch (err) {
        if (err?.name !== 'AbortError') results.innerHTML = `<div class="search-hint">${App.esc(err.message)}</div>`;
      }
    }, 160);
  });
})();

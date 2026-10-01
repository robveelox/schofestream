(() => {
  class SchofestreamError extends Error {
    constructor(message, status = 0) {
      super(message);
      this.name = 'SchofestreamError';
      this.status = status;
    }
  }

  const App = {
    csrf: document.querySelector('meta[name="csrf-token"]')?.content || '',

    async api(url, options = {}) {
      let timeoutId = 0;
      let controller = null;
      const requestOptions = { ...options };
      if (!requestOptions.signal && typeof AbortController !== 'undefined') {
        controller = new AbortController();
        requestOptions.signal = controller.signal;
        timeoutId = setTimeout(() => controller.abort(), 22000);
      }
      try {
        const response = await fetch(url, {
          credentials: 'same-origin',
          headers: { Accept: 'application/json', ...(requestOptions.headers || {}) },
          ...requestOptions,
        });
        let data = {};
        try { data = await response.json(); } catch (_) {}
        if (response.status === 401) {
          window.location.href = '/login.php';
          throw new SchofestreamError('Your session has expired.', 401);
        }
        if (!response.ok) throw new SchofestreamError(data.error || `Request failed (${response.status})`, response.status);
        return data;
      } catch (err) {
        if (err?.name === 'AbortError') throw err;
        if (err instanceof SchofestreamError) throw err;
        throw new SchofestreamError('Schofestream could not reach the server. Please try again.');
      } finally {
        if (timeoutId) clearTimeout(timeoutId);
      }
    },

    esc(value) {
      return String(value ?? '').replace(/[&<>'"]/g, ch => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', "'":'&#39;', '"':'&quot;' }[ch]));
    },

    duration(seconds) {
      const s = Math.max(0, Math.round(Number(seconds) || 0));
      const h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60);
      return h ? `${h}h ${m}m` : `${m}m`;
    },

    clock(seconds) {
      const total = Math.max(0, Math.floor(Number(seconds) || 0));
      const h = Math.floor(total / 3600);
      const m = Math.floor((total % 3600) / 60);
      const s = total % 60;
      return h ? `${h}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}` : `${m}:${String(s).padStart(2,'0')}`;
    },

    progress(item) {
      const total = Number(item?.runtimeSeconds) || 0;
      const pos = Number(item?.positionSeconds) || 0;
      if (!total || !pos) return '';
      const percent = Math.max(0, Math.min(100, (pos / total) * 100));
      return `<div class="progress-track" aria-hidden="true"><span style="width:${percent.toFixed(1)}%"></span></div>`;
    },

    episodeLabel(item) {
      if (item?.type !== 'Episode') return '';
      const bits = [];
      if (item.parentIndexNumber != null) bits.push(`S${String(item.parentIndexNumber).padStart(2, '0')}`);
      if (item.indexNumber != null) bits.push(`E${String(item.indexNumber).padStart(2, '0')}`);
      return bits.join(' ');
    },

    href(item, direct = false) {
      if (!item?.id) return '#';
      if (item.type === 'Episode') return `/watch.php?id=${encodeURIComponent(item.id)}`;
      if (item.type === 'Movie' && direct) return `/watch.php?id=${encodeURIComponent(item.id)}`;
      return `/details.php?id=${encodeURIComponent(item.id)}`;
    },

    art(image, alt = '') {
      return image
        ? `<img class="poster-img" src="${App.esc(image)}" alt="${App.esc(alt)}" loading="lazy" decoding="async">`
        : '<div class="art-placeholder" aria-hidden="true"><span>S</span></div>';
    },

    card(item, options = {}) {
      if (!item?.id) return '';
      const wide = !!options.wide;
      const direct = !!options.direct;
      const href = App.href(item, direct);
      const episode = App.episodeLabel(item);
      const title = item.type === 'Episode' && item.seriesName ? item.seriesName : (item.name || 'Untitled');
      const sub = item.type === 'Episode'
        ? [episode, item.name].filter(Boolean).join(' · ')
        : [item.year, item.type === 'Series' ? 'Series' : (item.type === 'BoxSet' ? 'Collection' : (item.runtimeSeconds ? App.duration(item.runtimeSeconds) : ''))].filter(Boolean).join(' · ');
      const image = wide ? (item.type === 'Episode' ? (item.thumb || item.backdrop || item.poster) : (item.backdrop || item.poster)) : item.poster;
      return `<a class="media-card ${wide ? 'wide-card' : ''}" href="${href}" aria-label="${App.esc(title)}${sub ? `, ${App.esc(sub)}` : ''}">
        <div class="media-art">${App.art(image)}<div class="card-play" aria-hidden="true">▶</div>${item.played ? '<span class="watched-badge">✓</span>' : ''}${App.progress(item)}</div>
        <div class="media-name">${App.esc(title)}</div>
        <div class="media-meta">${App.esc(sub)}</div>
      </a>`;
    },

    row(title, items, href = '', options = {}) {
      const cleanItems = Array.isArray(items) ? items.filter(Boolean) : [];
      if (!cleanItems.length) return '';
      const more = href ? `<a class="row-more" href="${href}">View all <span aria-hidden="true">→</span></a>` : '';
      return `<section class="media-row"><div class="row-heading"><h2>${App.esc(title)}</h2>${more}</div><div class="card-rail ${options.wide ? 'wide-rail' : ''}">${cleanItems.map(i => App.card(i, options)).join('')}</div></section>`;
    },

    skeletonRows(count = 3) {
      return Array.from({ length: count }, () => `<section class="media-row skeleton-row" aria-hidden="true"><div class="skeleton skeleton-title"></div><div class="card-rail">${Array.from({ length: 6 }, () => '<div class="skeleton-card"><div class="skeleton skeleton-poster"></div><div class="skeleton skeleton-line"></div></div>').join('')}</div></section>`).join('');
    },

    showError(container, message, options = {}) {
      if (!container) return;
      const home = options.home ? '<a class="btn btn-secondary" href="/">Go home</a>' : '';
      container.innerHTML = `<div class="error-state" role="alert"><strong>Something went wrong</strong><p>${App.esc(message || 'Please try again.')}</p><div class="error-actions"><button class="btn btn-primary" type="button" data-retry-page>Try again</button>${home}</div></div>`;
      container.querySelector('[data-retry-page]')?.addEventListener('click', () => location.reload());
    },

    toast(message) {
      document.querySelector('.app-toast')?.remove();
      const node = document.createElement('div');
      node.className = 'app-toast';
      node.setAttribute('role', 'status');
      node.setAttribute('aria-live', 'polite');
      node.textContent = message;
      document.body.appendChild(node);
      requestAnimationFrame(() => node.classList.add('show'));
      setTimeout(() => { node.classList.remove('show'); setTimeout(() => node.remove(), 250); }, 2200);
    },

    async setFavorite(id, favorite) {
      return App.api('/api/favorite.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, favorite, csrf: App.csrf }),
      });
    },

    async setWatched(id, played) {
      return App.api('/api/watched.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, played, csrf: App.csrf }),
      });
    },
  };

  window.Schofestream = App;

  document.addEventListener('error', e => {
    const img = e.target;
    if (img instanceof HTMLImageElement && img.classList.contains('poster-img')) {
      const parent = img.parentElement;
      img.remove();
      if (parent && !parent.querySelector('.art-placeholder')) parent.insertAdjacentHTML('afterbegin', '<div class="art-placeholder" aria-hidden="true"><span>S</span></div>');
    }
  }, true);

  const topbar = document.getElementById('topbar');
  if (topbar) {
    const update = () => topbar.classList.toggle('scrolled', window.scrollY > 20);
    update();
    window.addEventListener('scroll', update, { passive: true });
  }
})();

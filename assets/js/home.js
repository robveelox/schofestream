(async () => {
  const root=document.getElementById('homeApp'); if(!root)return;
  const rows=root.querySelector('.home-rows'); if(rows)rows.innerHTML=Schofestream.skeletonRows(4);
  const continueRow=items=>{
    if(!items?.length)return'';
    return `<section class="media-row"><div class="row-heading"><h2>Continue Watching</h2><a class="row-more" href="/history.php">History <span aria-hidden="true">→</span></a></div><div class="card-rail wide-rail">${items.map(item=>`<div class="continue-card" data-resume-id="${Schofestream.esc(item.id)}">${Schofestream.card(item,{wide:true,direct:true})}<button class="continue-remove" type="button" data-remove-resume="${Schofestream.esc(item.id)}" aria-label="Remove ${Schofestream.esc(item.name)} from Continue Watching">×</button></div>`).join('')}</div></section>`;
  };
  try{
    const data=await Schofestream.api('/api/home.php'), hero=data.hero;
    const heroPlay=hero?(hero.type==='Movie'?`/watch.php?id=${encodeURIComponent(hero.id)}`:`/details.php?id=${encodeURIComponent(hero.id)}`):'#';
    const heroHtml=hero?`<section class="hero"${hero.backdrop?` style="background-image:linear-gradient(90deg,rgba(4,9,17,.98) 0%,rgba(4,9,17,.76) 38%,rgba(4,9,17,.12) 78%),linear-gradient(0deg,#07111f 0%,transparent 42%),url('${Schofestream.esc(hero.backdrop)}')"`:''}><div class="hero-content"><div class="eyebrow">FOR ${Schofestream.esc((document.querySelector('.account-name')?.textContent||'YOU').toUpperCase())}</div><h1>${Schofestream.esc(hero.name)}</h1>${[hero.year,hero.officialRating,hero.runtimeSeconds?Schofestream.duration(hero.runtimeSeconds):''].filter(Boolean).length?`<div class="hero-meta">${[hero.year,hero.officialRating,hero.runtimeSeconds?Schofestream.duration(hero.runtimeSeconds):''].filter(Boolean).map(Schofestream.esc).join('<span>•</span>')}</div>`:''}<p>${Schofestream.esc(hero.overview||'Ready when you are.')}</p><div class="hero-actions"><a class="btn btn-primary" href="${heroPlay}">▶ ${hero.type==='Movie'?(hero.positionSeconds?'Resume':'Play'):'View series'}</a><a class="btn btn-glass" href="/details.php?id=${encodeURIComponent(hero.id)}">ⓘ More info</a></div></div></section>`:'<section class="hero empty-hero"><div class="hero-content"><h1>Welcome to Schofestream</h1><p>Add something to your Jellyfin library and it’ll appear here.</p></div></section>';
    const genreChips=(data.genres||[]).length?`<section class="media-row genre-strip"><div class="row-heading"><h2>Browse genres</h2><a class="row-more" href="/genres.php">View all <span aria-hidden="true">→</span></a></div><div class="genre-chips">${data.genres.slice(0,10).map(g=>`<a href="/genre.php?name=${encodeURIComponent(g.name)}">${Schofestream.esc(g.name)}</a>`).join('')}</div></section>`:'';
    const genreRows=(data.genreRows||[]).map(row=>Schofestream.row(row.name,row.items,`/genre.php?name=${encodeURIComponent(row.name)}`)).join('');
    const because=data.becauseYouWatched?.items?.length?Schofestream.row(`Because You Watched ${data.becauseYouWatched.source?.seriesName||data.becauseYouWatched.source?.name||''}`,data.becauseYouWatched.items):'';
    const available={continueWatching:continueRow(data.continueWatching),nextUp:Schofestream.row('Next Up',data.nextUp,'',{wide:true,direct:true}),becauseYouWatched:because,recentlyWatched:Schofestream.row('Recently Watched',data.recentlyWatched,'/history.php',{wide:true,direct:true}),favorites:Schofestream.row('My List',data.favorites,'/my-list.php'),recentMovies:Schofestream.row('Recently Added Movies',data.recentMovies,'/library.php?type=Movie'),recentShows:Schofestream.row('Recently Added TV',data.recentShows,'/library.php?type=Series'),genres:genreChips+genreRows,collections:Schofestream.row('Collections',data.collections,'/collections.php')};
    const hidden=new Set(data.preferences?.hidden_home_rows||[]);
    const ordered=(data.preferences?.home_rows||Object.keys(available)).filter(k=>!hidden.has(k)).map(k=>available[k]||'').join('');
    const partial=data.partial?'<div class="home-notice" role="status">Some rows couldn’t be loaded right now. The rest of Schofestream is still available.</div>':'';
    root.innerHTML=heroHtml+`<div class="content-shell home-rows">${partial}${ordered||'<div class="empty-state">Your library is ready, but there are no titles to show yet.</div>'}</div>`;
  }catch(err){Schofestream.showError(root,err.message)}

  root.addEventListener('click',async event=>{
    const button=event.target.closest('[data-remove-resume]'); if(!button)return;
    event.preventDefault(); event.stopPropagation(); button.disabled=true;
    try{await Schofestream.removeResume(button.dataset.removeResume); const card=button.closest('.continue-card'), rail=button.closest('.card-rail'); card?.classList.add('removing'); setTimeout(()=>{card?.remove(); if(rail&&!rail.children.length)rail.closest('.media-row')?.remove()},180); Schofestream.toast('Removed from Continue Watching');}
    catch(err){button.disabled=false;Schofestream.toast(err.message)}
  });
})();

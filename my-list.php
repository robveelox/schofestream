<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require_auth_page();
render_header('My List');
?>
<section class="page-shell" id="myListApp">
    <div class="page-heading library-heading">
        <div><div class="eyebrow">YOUR PICKS</div><h1>My List</h1><p class="muted" id="myListCount">Movies and shows you’ve saved.</p></div>
        <div class="library-controls"><label>Sort <select id="myListSort"><option value="title">A–Z</option><option value="added">Recently added</option><option value="year">Newest year</option><option value="watched">Recently watched</option></select></label></div>
    </div>
    <div id="myListGrid" class="poster-grid" aria-live="polite"><div class="loading-state"><span class="spinner"></span> Loading your list…</div></div>
</section>
<?php render_footer(['/assets/js/my-list.js']); ?>

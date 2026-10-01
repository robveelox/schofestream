<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require_auth_page();
render_header('Collections');
?>
<section class="page-shell" id="collectionsApp">
    <div class="page-heading"><div><div class="eyebrow">COLLECTIONS</div><h1>Collections</h1><p class="muted">Box sets and grouped titles from your Jellyfin library.</p></div></div>
    <div id="collectionsGrid" class="poster-grid"><div class="loading-state"><span class="spinner"></span> Loading collections…</div></div>
</section>
<?php render_footer(['/assets/js/collections.js']); ?>

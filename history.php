<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require_auth_page();
render_header('Watch History');
?>
<section class="page-shell" id="historyApp">
  <div class="page-heading library-heading"><div><div class="eyebrow">YOUR ACTIVITY</div><h1>Watch History</h1><p class="muted">Recently played movies and episodes from Jellyfin.</p></div>
  <div class="library-controls"><label>Show <select id="historyType"><option value="All">Everything</option><option value="Movie">Movies</option><option value="Episode">TV Episodes</option></select></label></div></div>
  <div id="historyGrid" class="history-list"><div class="loading-state"><span class="spinner"></span> Loading history…</div></div>
</section>
<?php render_footer(['/assets/js/history.js']); ?>

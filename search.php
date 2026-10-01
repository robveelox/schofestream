<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require_auth_page();
$q = trim((string)($_GET['q'] ?? ''));
render_header($q ? 'Search: ' . $q : 'Search');
?>
<section class="page-shell" id="searchApp" data-query="<?= e($q) ?>">
    <div class="page-heading"><div><div class="eyebrow">SEARCH</div><h1><?= $q ? 'Results for “' . e($q) . '”' : 'Search Schofestream' ?></h1></div></div>
    <form class="big-search" action="/search.php" method="get"><input type="search" name="q" value="<?= e($q) ?>" placeholder="Movie, TV show, episode or person" autofocus><button class="btn btn-primary">Search</button></form>
    <div id="searchResults"><?= $q ? '<div class="loading-state"><span class="spinner"></span> Searching…</div>' : '<div class="empty-state">Search your entire Schofestream library.</div>' ?></div>
</section>
<?php render_footer(['/assets/js/search.js']); ?>

<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require_auth_page();
$type = ($_GET['type'] ?? 'Movie') === 'Series' ? 'Series' : 'Movie';
$title = $type === 'Series' ? 'TV Shows' : 'Movies';
render_header($title);
?>
<section class="page-shell" id="libraryApp" data-type="<?= e($type) ?>">
    <div class="page-heading library-heading">
        <div><div class="eyebrow">LIBRARY</div><h1><?= e($title) ?></h1><p class="muted" id="libraryCount"></p></div>
        <div class="library-controls">
            <label>Sort <select id="librarySort"><option value="title">A–Z</option><option value="recent">Recently added</option><option value="year">Newest year</option><option value="rating">Top rated</option></select></label>
        </div>
    </div>
    <div id="libraryGrid" class="poster-grid"><div class="loading-state"><span class="spinner"></span> Loading <?= e(strtolower($title)) ?>…</div></div>
</section>
<?php render_footer(['/assets/js/library.js']); ?>

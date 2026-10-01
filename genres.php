<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require_auth_page();
render_header('Genres');
?>
<section class="page-shell" id="genresApp">
    <div class="page-heading"><div><div class="eyebrow">DISCOVER</div><h1>Browse by genre</h1><p class="muted">Find something that matches the mood.</p></div></div>
    <div id="genreGrid" class="genre-grid"><div class="loading-state"><span class="spinner"></span> Loading genres…</div></div>
</section>
<?php render_footer(['/assets/js/genres.js']); ?>

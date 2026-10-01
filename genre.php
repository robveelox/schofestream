<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require_auth_page();
$name = trim((string)($_GET['name'] ?? ''));
if ($name === '') { http_response_code(400); exit('Missing genre.'); }
render_header($name);
?>
<section class="page-shell" id="genreApp" data-name="<?= e($name) ?>">
    <div class="page-heading"><div><div class="eyebrow">GENRE</div><h1><?= e($name) ?></h1></div></div>
    <div id="genreItems" class="poster-grid"><div class="loading-state"><span class="spinner"></span> Loading titles…</div></div>
</section>
<?php render_footer(['/assets/js/genres.js']); ?>

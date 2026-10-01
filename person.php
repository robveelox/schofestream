<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require_auth_page();
$name = trim((string)($_GET['name'] ?? ''));
if ($name === '') { http_response_code(400); exit('Missing person name.'); }
render_header($name);
?>
<section class="page-shell" id="personApp" data-name="<?= e($name) ?>">
    <div class="loading-state"><span class="spinner"></span> Loading profile…</div>
</section>
<?php render_footer(['/assets/js/person.js']); ?>

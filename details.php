<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require_auth_page();
$id = trim((string)($_GET['id'] ?? ''));
if ($id === '') {
    http_response_code(400);
    exit('Missing item id.');
}
render_header('Title details');
?>
<section id="detailsApp" class="details-app" data-id="<?= e($id) ?>" aria-live="polite">
    <div class="details-loading"><span class="spinner"></span><span>Loading title…</span></div>
</section>
<?php render_footer(['/assets/js/details.js']); ?>

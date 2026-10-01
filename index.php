<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require_auth_page();
render_header('Schofestream');
?>
<section id="homeApp" class="home-app" aria-live="polite">
    <div class="hero skeleton-hero" aria-hidden="true"></div>
    <div class="content-shell home-rows"></div>
</section>
<?php render_footer(['/assets/js/home.js']); ?>

<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require_auth_page();
render_header('Settings');
?>
<section class="page-shell narrow settings-page" id="settingsApp">
    <div class="page-heading"><div><div class="eyebrow">PERSONALISE SCHOFE STREAM</div><h1>Settings</h1><p class="muted">Choose how Schofestream behaves for your profile.</p></div></div>
    <div class="settings-loading loading-state"><span class="spinner"></span> Loading your preferences…</div>
</section>
<?php render_footer(['/assets/js/settings.js']); ?>

<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require_auth_page();
render_header('Profile');
?>
<section class="page-shell account-page" id="accountApp"><div class="loading-state"><span class="spinner"></span> Loading your profile…</div></section>
<?php render_footer(['/assets/js/account.js']); ?>

<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require_household_page();
sf_household_load(true);
render_header("Who's Watching?", false, 'profiles-page');
?>
<header class="profiles-topbar">
    <a class="brand" href="/profiles.php" aria-label="Schofestream profiles"><span class="brand-mark">S</span><span>SCHOFE<span>STREAM</span></span></a>
    <a class="profiles-signout" href="/logout.php">Sign out</a>
</header>
<section class="profiles-shell" id="profilesApp">
    <div class="profiles-loading"><span class="spinner"></span><span>Loading profiles…</span></div>
</section>
<?php render_footer(['/assets/js/profiles.js'], false); ?>

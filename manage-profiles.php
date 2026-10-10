<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require_household_page();
sf_household_load(true);
render_header('Manage Profiles', false, 'profiles-page');
?>
<header class="profiles-topbar">
    <a class="brand" href="/" aria-label="Schofestream home"><span class="brand-mark">S</span><span>SCHOFE<span>STREAM</span></span></a>
    <div class="profiles-top-actions"><a href="/profiles.php">Done</a><a href="/logout.php">Sign out</a></div>
</header>
<section class="profile-manage-shell" id="manageProfilesApp">
    <div class="profiles-loading"><span class="spinner"></span><span>Loading household…</span></div>
</section>
<?php render_footer(['/assets/js/manage-profiles.js'], false); ?>

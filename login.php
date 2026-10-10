<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
if (sf_household_authenticated()) {
    header('Location: ' . (sf_profile_selected() ? '/' : '/profiles.php'));
    exit;
}
render_header('Sign in', false, 'login-page');
?>
<section class="login-shell">
    <div class="login-art">
        <div class="login-glow"></div>
        <div class="login-copy">
            <div class="brand login-brand"><span class="brand-mark">S</span><span>SCHOFE<span>STREAM</span></span></div>
            <h1>Your movies.<br>Your shows.<br>Your screen.</h1>
            <p>One household sign-in, separate viewing profiles.</p>
        </div>
    </div>
    <div class="login-panel">
        <form id="loginForm" class="login-card" autocomplete="on">
            <div class="eyebrow">HOUSEHOLD SIGN IN</div>
            <h2>Sign in to Schofestream</h2>
            <p class="muted">Use the Jellyfin username and password that will own this Schofestream household.</p>
            <label>Username<input name="username" type="text" autocomplete="username" required autofocus></label>
            <label>Password<input name="password" type="password" autocomplete="current-password"></label>
            <div id="loginError" class="form-error" role="alert" hidden></div>
            <button type="submit" class="btn btn-primary btn-wide"><span>Sign in</span></button>
        </form>
    </div>
</section>
<?php render_footer(['/assets/js/login.js']); ?>

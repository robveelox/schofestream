<?php
declare(strict_types=1);

function render_header(string $title = 'Schofestream', bool $showNav = true, string $bodyClass = ''): void
{
    global $config;
    $appName = (string)$config['app_name'];
    $username = (string)($_SESSION['jf_username'] ?? '');
    $fullTitle = $title === $appName ? $appName : $title . ' · ' . $appName;
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#07111f">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($fullTitle) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= e((string)$config['app_version']) ?>">
</head>
<body class="<?= e($bodyClass) ?>">
<a class="skip-link" href="#mainContent">Skip to content</a>
<?php if ($showNav): ?>
<header class="topbar" id="topbar">
    <a class="brand" href="/" aria-label="Schofestream home"><span class="brand-mark">S</span><span>SCHOFE<span>STREAM</span></span></a>
    <nav class="main-nav" aria-label="Main navigation">
        <a href="/">Home</a>
        <a href="/library.php?type=Movie">Movies</a>
        <a href="/library.php?type=Series">TV Shows</a>
        <a href="/my-list.php">My List</a>
        <a href="/genres.php">Genres</a>
    </nav>
    <button class="nav-search-button" id="openSearch" type="button" aria-label="Search Schofestream">⌕ <span>Search</span></button>
    <div class="account-menu">
        <a class="account-profile-link" href="/account.php" aria-label="Open profile"><span class="avatar-dot"><?= e(strtoupper(substr($username ?: 'S', 0, 1))) ?></span><span class="account-name"><?= e($username) ?></span></a>
        <a class="settings-link" href="/settings.php" aria-label="Settings">⚙</a>
        <a class="logout-link" href="/logout.php">Sign out</a>
    </div>
</header>

<div class="search-overlay" id="searchOverlay" hidden>
    <div class="search-overlay-backdrop" data-search-close></div>
    <section class="search-panel" role="dialog" aria-modal="true" aria-label="Search Schofestream">
        <div class="search-panel-top">
            <span class="search-icon">⌕</span>
            <input id="instantSearchInput" type="search" autocomplete="off" placeholder="Search movies, shows, episodes or people" aria-label="Search Schofestream" aria-controls="instantSearchResults" aria-autocomplete="list">
            <button class="icon-btn search-close" type="button" data-search-close aria-label="Close search">×</button>
        </div>
        <div id="instantSearchResults" class="instant-search-results">
            <div class="search-hint">Start typing to search your Schofestream library.</div>
        </div>
    </section>
</div>

<nav class="mobile-nav mobile-nav-six" aria-label="Mobile navigation">
    <a href="/"><span>⌂</span><small>Home</small></a>
    <a href="/library.php?type=Movie"><span>▣</span><small>Movies</small></a>
    <a href="/library.php?type=Series"><span>▤</span><small>TV</small></a>
    <button id="mobileSearch" type="button"><span>⌕</span><small>Search</small></button>
    <a href="/my-list.php"><span>＋</span><small>My List</small></a>
    <a href="/account.php"><span>●</span><small>Profile</small></a>
</nav>
<?php endif; ?>
<main id="mainContent">
    <?php
}

function render_footer(array $scripts = []): void
{
    global $config;
    ?>
</main>
<footer class="site-footer">
    <div><span class="brand-small">Schofestream</span> <span class="muted">v<?= e((string)$config['app_version']) ?></span></div>
    <div class="muted">Powered by your Jellyfin library</div>
</footer>
<script src="/assets/js/app.js?v=<?= e((string)$config['app_version']) ?>"></script>
<script src="/assets/js/global-search.js?v=<?= e((string)$config['app_version']) ?>"></script>
<?php foreach ($scripts as $script): ?>
<script src="<?= e($script) ?>?v=<?= e((string)$config['app_version']) ?>"></script>
<?php endforeach; ?>
</body>
</html>
    <?php
}

<?php
declare(strict_types=1);


function ui_icon(string $name): string
{
    static $icons = [
        'search' => '<circle cx="11" cy="11" r="6.5"></circle><path d="m16 16 4.2 4.2"></path>',
        'settings' => '<circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.86 2.86-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21h-4v-.1A1.7 1.7 0 0 0 8.6 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06-2.86-2.86.06-.06A1.7 1.7 0 0 0 4.2 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1.1-.4H2.4v-4h.1A1.7 1.7 0 0 0 4.2 8.6a1.7 1.7 0 0 0-.34-1.88l-.06-.06L6.66 3.8l.06.06A1.7 1.7 0 0 0 8.6 4.2a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1.1V2.4h4v.1A1.7 1.7 0 0 0 15 4.2a1.7 1.7 0 0 0 1.88-.34l.06-.06 2.86 2.86-.06.06A1.7 1.7 0 0 0 19.4 8.6a1.7 1.7 0 0 0 .6 1 1.7 1.7 0 0 0 1.1.4h.1v4h-.1a1.7 1.7 0 0 0-1.7 1z"></path>',
        'logout' => '<path d="M9 5H5.5A2.5 2.5 0 0 0 3 7.5v9A2.5 2.5 0 0 0 5.5 19H9"></path><path d="M14 8l4 4-4 4"></path><path d="M18 12H8"></path>',
        'home' => '<path d="M3.5 11.5 12 4l8.5 7.5"></path><path d="M5.5 10.5V20h13v-9.5"></path><path d="M9.5 20v-6h5v6"></path>',
        'movie' => '<rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="M3 9h18M8 5v4M16 5v4M8 15h8"></path>',
        'tv' => '<rect x="3" y="6" width="18" height="13" rx="2"></rect><path d="m9 3 3 3 3-3M8 22h8"></path>',
        'plus' => '<path d="M12 5v14M5 12h14"></path>',
        'profile' => '<circle cx="12" cy="8" r="4"></circle><path d="M4.5 21a7.5 7.5 0 0 1 15 0"></path>',
        'close' => '<path d="M6 6l12 12M18 6 6 18"></path>',
        'play' => '<path d="M8 5.5 18 12 8 18.5z" fill="currentColor" stroke="none"></path>',
        'back' => '<path d="M19 12H5M11 6l-6 6 6 6"></path>',
        'rewind' => '<path d="M11 7 5 12l6 5zM19 7l-6 5 6 5z"></path>',
        'forward' => '<path d="m5 7 6 5-6 5zM13 7l6 5-6 5z"></path>',
        'volume' => '<path d="M4 10v4h4l5 4V6L8 10H4z"></path><path d="M16 9.5a4 4 0 0 1 0 5M18.5 7a7.5 7.5 0 0 1 0 10"></path>',
        'fullscreen' => '<path d="M8 3H3v5M16 3h5v5M8 21H3v-5M16 21h5v-5"></path>',
    ];
    $body = $icons[$name] ?? '';
    return '<svg class="ui-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $body . '</svg>';
}

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
    <button class="nav-search-button" id="openSearch" type="button" aria-label="Search Schofestream"><?= ui_icon('search') ?> <span>Search</span></button>
    <div class="account-menu">
        <a class="account-profile-link" href="/account.php" aria-label="Open profile"><span class="avatar-dot"><?= e(strtoupper(substr($username ?: 'S', 0, 1))) ?></span><span class="account-name"><?= e($username) ?></span></a>
        <a class="settings-link" href="/settings.php" aria-label="Settings"><?= ui_icon('settings') ?></a>
        <a class="logout-link" href="/logout.php">Sign out</a>
        <a class="mobile-logout-link" href="/logout.php" aria-label="Sign out" title="Sign out"><?= ui_icon('logout') ?></a>
    </div>
</header>

<div class="search-overlay" id="searchOverlay" hidden>
    <div class="search-overlay-backdrop" data-search-close></div>
    <section class="search-panel" role="dialog" aria-modal="true" aria-label="Search Schofestream">
        <div class="search-panel-top">
            <span class="search-icon"><?= ui_icon('search') ?></span>
            <input id="instantSearchInput" type="search" autocomplete="off" placeholder="Search movies, shows, episodes or people" aria-label="Search Schofestream" aria-controls="instantSearchResults" aria-autocomplete="list">
            <button class="icon-btn search-close" type="button" data-search-close aria-label="Close search"><?= ui_icon('close') ?></button>
        </div>
        <div id="instantSearchResults" class="instant-search-results">
            <div class="search-hint">Start typing to search your Schofestream library.</div>
        </div>
    </section>
</div>

<nav class="mobile-nav mobile-nav-six" aria-label="Mobile navigation">
    <a href="/"><span class="nav-icon"><?= ui_icon('home') ?></span><small>Home</small></a>
    <a href="/library.php?type=Movie"><span class="nav-icon"><?= ui_icon('movie') ?></span><small>Movies</small></a>
    <a href="/library.php?type=Series"><span class="nav-icon"><?= ui_icon('tv') ?></span><small>TV</small></a>
    <button id="mobileSearch" type="button"><span class="nav-icon"><?= ui_icon('search') ?></span><small>Search</small></button>
    <a href="/my-list.php"><span class="nav-icon"><?= ui_icon('plus') ?></span><small>My List</small></a>
    <a href="/account.php"><span class="nav-icon"><?= ui_icon('profile') ?></span><small>Profile</small></a>
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

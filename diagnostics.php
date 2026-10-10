<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require_auth_page();
render_header('Diagnostics');
$checks = [
    'PHP 8.1+' => version_compare(PHP_VERSION, '8.1.0', '>='),
    'cURL extension' => extension_loaded('curl'),
    'JSON extension' => extension_loaded('json'),
    'OpenSSL extension' => extension_loaded('openssl'),
    'Session support' => function_exists('session_start'),
];
try { $settingsFile = sf_settings_file(); $checks['Preferences storage'] = is_dir(dirname($settingsFile)) && is_writable(dirname($settingsFile)); } catch (Throwable) { $checks['Preferences storage'] = false; }
try { $householdFile = sf_household_file(); $checks['Household storage'] = is_dir(dirname($householdFile)) && is_writable(dirname($householdFile)); } catch (Throwable) { $checks['Household storage'] = false; }
try { sf_profile_secret_key(); $checks['Profile encryption key'] = true; } catch (Throwable) { $checks['Profile encryption key'] = false; }
$server = null;
$error = null;
try {
    $server = jf_request('GET', '/System/Info/Public', [], null, false);
} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>
<section class="page-shell narrow">
    <div class="page-heading"><div><div class="eyebrow">SCHOFESTREAM <?= e((string)$config['app_version']) ?></div><h1>Diagnostics</h1></div></div>
    <div class="diagnostic-card">
        <?php foreach ($checks as $label => $ok): ?><div class="diag-row"><span><?= e($label) ?></span><strong class="<?= $ok ? 'ok' : 'bad' ?>"><?= $ok ? 'OK' : 'FAIL' ?></strong></div><?php endforeach; ?>
        <div class="diag-row"><span>Active profile</span><strong><?= e(sf_profile_name()) ?></strong></div>
        <div class="diag-row"><span>Client platform</span><strong><?= sf_is_ps4_browser() ? 'PlayStation 4 compatibility mode' : 'Web browser' ?></strong></div>
        <?php if (sf_is_ps4_browser()): ?><div class="diag-row"><span>PS4 playback profile</span><strong class="ok">Forced H.264 Main 4.0 / AAC / 720p + MP4 fallback</strong></div><?php endif; ?>
        <div class="diag-row"><span>Jellyfin connection</span><strong class="<?= $server ? 'ok' : 'bad' ?>"><?= $server ? 'OK' : 'FAIL' ?></strong></div>
        <?php if ($server): ?><div class="diag-row"><span>Jellyfin version</span><strong><?= e((string)($server['Version'] ?? 'Unknown')) ?></strong></div><?php endif; ?>
        <?php if ($error): ?><p class="form-error"><?= e($error) ?></p><?php endif; ?>
    </div>
</section>
<?php render_footer(); ?>

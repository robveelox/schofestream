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
    'Session support' => function_exists('session_start'),
];
$server = null;
$error = null;
try {
    $server = jf_request('GET', '/System/Info/Public', [], null, false);
} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>
<section class="page-shell narrow">
    <div class="page-heading"><div><div class="eyebrow">SCHOFESTREAM 0.1</div><h1>Diagnostics</h1></div></div>
    <div class="diagnostic-card">
        <?php foreach ($checks as $label => $ok): ?><div class="diag-row"><span><?= e($label) ?></span><strong class="<?= $ok ? 'ok' : 'bad' ?>"><?= $ok ? 'OK' : 'FAIL' ?></strong></div><?php endforeach; ?>
        <div class="diag-row"><span>Jellyfin connection</span><strong class="<?= $server ? 'ok' : 'bad' ?>"><?= $server ? 'OK' : 'FAIL' ?></strong></div>
        <?php if ($server): ?><div class="diag-row"><span>Jellyfin version</span><strong><?= e((string)($server['Version'] ?? 'Unknown')) ?></strong></div><?php endif; ?>
        <?php if ($error): ?><p class="form-error"><?= e($error) ?></p><?php endif; ?>
    </div>
</section>
<?php render_footer(); ?>

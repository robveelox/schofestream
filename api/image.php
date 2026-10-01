<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_auth_api();

$id = preg_replace('/[^A-Za-z0-9_-]/', '', (string)($_GET['id'] ?? ''));
$type = (string)($_GET['type'] ?? 'Primary');
$allowedTypes = ['Primary', 'Backdrop', 'Logo', 'Thumb'];
if (!in_array($type, $allowedTypes, true)) {
    $type = 'Primary';
}
$width = max(120, min(2000, (int)($_GET['width'] ?? 720)));
if ($id === '') {
    http_response_code(404);
    exit;
}

try {
    $result = jf_request('GET', "/Items/{$id}/Images/{$type}", [
        'MaxWidth' => $width,
        'Quality' => max(65, min(95, (int)($config['image_quality'] ?? 84))),
    ], null, true, true);
    if ($result['status'] < 200 || $result['status'] >= 300) {
        http_response_code(404);
        exit;
    }
    header('Content-Type: ' . $result['content_type']);
    header('Cache-Control: private, max-age=86400');
    echo $result['body'];
} catch (Throwable) {
    http_response_code(404);
}

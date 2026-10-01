<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_auth_api();
try {
    $raw = jf_request('GET', '/Users/' . rawurlencode(jf_user_id()) . '/Images/Primary', ['maxWidth' => 320, 'quality' => 88], null, true, true);
    if (($raw['status'] ?? 500) < 200 || ($raw['status'] ?? 500) >= 300) { http_response_code(404); exit; }
    header('Content-Type: ' . ($raw['content_type'] ?? 'image/jpeg'));
    header('Cache-Control: private, max-age=300');
    echo $raw['body'];
    exit;
} catch (Throwable) { http_response_code(404); exit; }

<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_auth_api();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}
$data = json_input();
verify_csrf((string)($data['csrf'] ?? ''));
$id = trim((string)($data['id'] ?? ''));
$favorite = (bool)($data['favorite'] ?? false);
if ($id === '') {
    json_response(['error' => 'Missing item id'], 422);
}

$userId = jf_user_id();
try {
    jf_request($favorite ? 'POST' : 'DELETE', "/Users/{$userId}/FavoriteItems/{$id}");
    jf_cache_clear();
    json_response(['ok' => true, 'favorite' => $favorite]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 502);
}

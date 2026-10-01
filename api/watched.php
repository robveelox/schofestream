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
$played = (bool)($data['played'] ?? false);
if ($id === '') {
    json_response(['error' => 'Missing item id'], 422);
}

$userId = jf_user_id();
try {
    jf_request($played ? 'POST' : 'DELETE', "/Users/{$userId}/PlayedItems/{$id}");
    jf_cache_clear();
    json_response(['ok' => true, 'played' => $played]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 502);
}

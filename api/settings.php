<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_auth_api();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    json_response(['preferences' => sf_preferences(), 'defaults' => sf_default_preferences()]);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'Method not allowed'], 405);
$data = json_input();
verify_csrf((string)($data['csrf'] ?? ''));
unset($data['csrf']);
try {
    $prefs = sf_save_preferences($data);
    json_response(['ok' => true, 'preferences' => $prefs]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}

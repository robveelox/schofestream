<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_auth_api();
json_response([
    'authenticated' => true,
    'user' => [
        'id' => jf_user_id(),
        'name' => (string)($_SESSION['jf_username'] ?? ''),
    ],
    'csrf' => csrf_token(),
]);

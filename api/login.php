<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$data = json_input();
verify_csrf((string)($data['csrf'] ?? ''));
$username = trim((string)($data['username'] ?? ''));
$password = (string)($data['password'] ?? '');

if ($username === '') {
    json_response(['error' => 'Enter your username.'], 422);
}

try {
    $auth = jf_request('POST', '/Users/AuthenticateByName', [], [
        'Username' => $username,
        'Pw' => $password,
    ], false);

    if (empty($auth['AccessToken']) || empty($auth['User']['Id'])) {
        throw new RuntimeException('Jellyfin did not return a valid login session.');
    }

    session_regenerate_id(true);
    $_SESSION['jf_token'] = (string)$auth['AccessToken'];
    $_SESSION['jf_user_id'] = (string)$auth['User']['Id'];
    $_SESSION['jf_username'] = (string)($auth['User']['Name'] ?? $username);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    $_SESSION['login_at'] = gmdate('c');

    json_response(['ok' => true, 'user' => ['name' => $_SESSION['jf_username']]]);
} catch (Throwable $e) {
    $status = $e->getCode() === 401 ? 401 : 502;
    json_response(['error' => $status === 401 ? 'Incorrect username or password.' : $e->getMessage()], $status);
}

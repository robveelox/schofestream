<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

// Only end the household owner's login session. Linked profile tokens are deliberately
// retained (encrypted outside the web root) so they can be selected again after the next
// household sign-in. Removing/relinking a profile revokes its stored token separately.
$ownerToken = (string)($_SESSION['household_owner_token'] ?? '');
if ($ownerToken !== '') {
    try { jf_request_with_token($ownerToken, 'POST', '/Sessions/Logout', [], null, false, (string)($_SESSION['household_owner_device_id'] ?? '')); } catch (Throwable) {}
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool)$params['secure'], (bool)$params['httponly']);
}
session_destroy();
header('Location: /login.php');
exit;

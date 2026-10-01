<?php
declare(strict_types=1);

$config = require __DIR__ . '/../config.php';

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

session_name((string)$config['session_name']);
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

if (!isset($_SESSION['device_id'])) {
    $_SESSION['device_id'] = bin2hex(random_bytes(16));
}

$jfOrigin = parse_url((string)$config['jellyfin_url'], PHP_URL_SCHEME) . '://' . parse_url((string)$config['jellyfin_url'], PHP_URL_HOST);
if ($port = parse_url((string)$config['jellyfin_url'], PHP_URL_PORT)) {
    $jfOrigin .= ':' . $port;
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header("Content-Security-Policy: default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; img-src 'self' data: blob:; font-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' https://cdn.jsdelivr.net; worker-src 'self' blob:; connect-src 'self' {$jfOrigin}; media-src 'self' {$jfOrigin} blob: data:");

require_once __DIR__ . '/jellyfin.php';

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_logged_in(): bool
{
    return !empty($_SESSION['jf_token']) && !empty($_SESSION['jf_user_id']);
}

function require_auth_page(): void
{
    if (!is_logged_in()) {
        header('Location: /login.php');
        exit;
    }
}

function require_auth_api(): void
{
    if (!is_logged_in()) {
        json_response(['error' => 'Authentication required'], 401);
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(?string $token): void
{
    $expected = $_SESSION['csrf_token'] ?? '';
    if (!$token || !$expected || !hash_equals($expected, $token)) {
        json_response(['error' => 'Invalid security token. Refresh the page and try again.'], 419);
    }
}

function json_input(): array
{
    $raw = file_get_contents('php://input') ?: '';
    if ($raw === '') {
        return $_POST ?: [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function ticks_to_seconds(int|float|null $ticks): float
{
    return max(0, ((float)($ticks ?? 0)) / 10_000_000);
}

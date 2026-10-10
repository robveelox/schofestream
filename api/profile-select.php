<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_household_api();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}
$data = json_input();
verify_csrf((string)($data['csrf'] ?? ''));
$profileId = (string)($data['profile_id'] ?? '');
$pin = (string)($data['pin'] ?? '');

try {
    $household = sf_household_load(true);
    $profile = sf_household_profile($household, $profileId);
    if (!$profile) throw new RuntimeException('Profile not found.', 404);
    sf_select_profile($profile, $pin);
    json_response([
        'ok' => true,
        'profile' => [
            'id' => (string)$profile['id'],
            'name' => (string)$profile['name'],
            'avatar' => sf_sanitize_avatar_key((string)($profile['avatar'] ?? 'ocean')),
        ],
        'redirect' => '/',
    ]);
} catch (Throwable $e) {
    $code = (int)$e->getCode();
    if ($code === 409 && $profileId !== '') {
        try {
            $household = sf_household_load(true);
            foreach ($household['profiles'] as &$savedProfile) {
                if ((string)($savedProfile['id'] ?? '') === $profileId && empty($savedProfile['is_owner'])) {
                    $savedProfile['token'] = null;
                    break;
                }
            }
            unset($savedProfile);
            sf_household_save($household);
        } catch (Throwable) {}
    }
    $status = in_array($code, [403, 404, 409], true) ? $code : 500;
    json_response(['error' => $e->getMessage()], $status);
}

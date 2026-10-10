<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_household_api();

function household_profile_index(array $household, string $id): int
{
    foreach ((array)($household['profiles'] ?? []) as $index => $profile) {
        if (is_array($profile) && (string)($profile['id'] ?? '') === $id) return (int)$index;
    }
    return -1;
}

function validate_profile_pin(string $pin): ?string
{
    $pin = trim($pin);
    if ($pin === '') return null;
    if (!preg_match('/^\d{4}$/', $pin)) {
        throw new RuntimeException('Profile PINs must be exactly 4 digits.', 422);
    }
    return password_hash($pin, PASSWORD_DEFAULT);
}

function authenticate_linked_user(string $username, string $password): array
{
    if (trim($username) === '') throw new RuntimeException('Enter the Jellyfin username to link.', 422);
    $auth = jf_request('POST', '/Users/AuthenticateByName', [], [
        'Username' => trim($username),
        'Pw' => $password,
    ], false);
    if (empty($auth['AccessToken']) || empty($auth['User']['Id'])) {
        throw new RuntimeException('Jellyfin did not return a valid user session.');
    }
    return $auth;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $household = sf_household_load(true);
        $public = sf_household_public($household);
        $public['maxProfiles'] = max(1, (int)($config['max_profiles'] ?? 5));
        $public['avatarKeys'] = sf_profile_avatar_keys();
        $public['ownerUsername'] = sf_household_owner_name();
        json_response($public);
    } catch (Throwable $e) {
        json_response(['error' => $e->getMessage()], 500);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$data = json_input();
verify_csrf((string)($data['csrf'] ?? ''));
$action = (string)($data['action'] ?? '');

try {
    $household = sf_household_load(true);
    $profiles = (array)($household['profiles'] ?? []);

    if ($action === 'create') {
        $max = max(1, (int)($config['max_profiles'] ?? 5));
        if (count($profiles) >= $max) {
            throw new RuntimeException("This household already has the maximum of {$max} profiles.", 422);
        }

        $name = sf_sanitize_profile_name((string)($data['name'] ?? ''));
        $avatar = sf_sanitize_avatar_key((string)($data['avatar'] ?? 'ocean'));
        $pinHash = validate_profile_pin((string)($data['pin'] ?? ''));
        $auth = authenticate_linked_user((string)($data['jellyfin_username'] ?? ''), (string)($data['jellyfin_password'] ?? ''));
        $userId = (string)$auth['User']['Id'];
        $userName = (string)($auth['User']['Name'] ?? $data['jellyfin_username']);

        foreach ($profiles as $profile) {
            if ((string)($profile['jellyfin_user_id'] ?? '') === $userId) {
                throw new RuntimeException('That Jellyfin user is already linked to this household.', 409);
            }
        }

        $profiles[] = [
            'id' => sf_new_profile_id(),
            'name' => $name,
            'jellyfin_user_id' => $userId,
            'jellyfin_username' => $userName,
            'avatar' => $avatar,
            'is_owner' => false,
            'pin_hash' => $pinHash,
            'token' => sf_encrypt_profile_token((string)$auth['AccessToken']),
            'device_id' => (string)($_SESSION['device_id'] ?? ''),
            'created_at' => gmdate('c'),
        ];
        $household['profiles'] = $profiles;
        sf_household_save($household);
        json_response(['ok' => true, 'household' => sf_household_public($household)], 201);
    }

    if ($action === 'update') {
        $profileId = (string)($data['profile_id'] ?? '');
        $index = household_profile_index($household, $profileId);
        if ($index < 0) throw new RuntimeException('Profile not found.', 404);

        $household['profiles'][$index]['name'] = sf_sanitize_profile_name((string)($data['name'] ?? ''));
        $household['profiles'][$index]['avatar'] = sf_sanitize_avatar_key((string)($data['avatar'] ?? 'ocean'));

        if (!empty($data['clear_pin'])) {
            $household['profiles'][$index]['pin_hash'] = null;
        } elseif (array_key_exists('pin', $data) && trim((string)$data['pin']) !== '') {
            $household['profiles'][$index]['pin_hash'] = validate_profile_pin((string)$data['pin']);
        }

        sf_household_save($household);
        if ((string)($_SESSION['active_profile_id'] ?? '') === $profileId) {
            $_SESSION['active_profile_name'] = (string)$household['profiles'][$index]['name'];
            $_SESSION['active_profile_avatar'] = (string)$household['profiles'][$index]['avatar'];
        }
        json_response(['ok' => true, 'household' => sf_household_public($household)]);
    }

    if ($action === 'relink') {
        $profileId = (string)($data['profile_id'] ?? '');
        $index = household_profile_index($household, $profileId);
        if ($index < 0) throw new RuntimeException('Profile not found.', 404);
        if (!empty($household['profiles'][$index]['is_owner'])) {
            throw new RuntimeException('The household owner is relinked by signing out and back in.', 422);
        }

        $auth = authenticate_linked_user((string)($data['jellyfin_username'] ?? ''), (string)($data['jellyfin_password'] ?? ''));
        $userId = (string)$auth['User']['Id'];
        foreach ($household['profiles'] as $i => $profile) {
            if ($i !== $index && (string)($profile['jellyfin_user_id'] ?? '') === $userId) {
                throw new RuntimeException('That Jellyfin user is already linked to another profile.', 409);
            }
        }

        $oldTokenPayload = is_array($household['profiles'][$index]['token'] ?? null) ? $household['profiles'][$index]['token'] : null;
        if ($oldTokenPayload) {
            try { jf_request_with_token(sf_decrypt_profile_token($oldTokenPayload), 'POST', '/Sessions/Logout', [], null, false, (string)($household['profiles'][$index]['device_id'] ?? '')); } catch (Throwable) {}
        }

        $household['profiles'][$index]['jellyfin_user_id'] = $userId;
        $household['profiles'][$index]['jellyfin_username'] = (string)($auth['User']['Name'] ?? $data['jellyfin_username']);
        $household['profiles'][$index]['token'] = sf_encrypt_profile_token((string)$auth['AccessToken']);
        $household['profiles'][$index]['device_id'] = (string)($_SESSION['device_id'] ?? '');
        sf_household_save($household);
        json_response(['ok' => true, 'household' => sf_household_public($household)]);
    }

    if ($action === 'delete') {
        $profileId = (string)($data['profile_id'] ?? '');
        $index = household_profile_index($household, $profileId);
        if ($index < 0) throw new RuntimeException('Profile not found.', 404);
        if (!empty($household['profiles'][$index]['is_owner'])) {
            throw new RuntimeException('The household owner profile cannot be deleted.', 422);
        }
        if ((string)($_SESSION['active_profile_id'] ?? '') === $profileId) {
            throw new RuntimeException('Switch to another profile before deleting this one.', 409);
        }

        $tokenPayload = is_array($household['profiles'][$index]['token'] ?? null) ? $household['profiles'][$index]['token'] : null;
        if ($tokenPayload) {
            try { jf_request_with_token(sf_decrypt_profile_token($tokenPayload), 'POST', '/Sessions/Logout', [], null, false, (string)($household['profiles'][$index]['device_id'] ?? '')); } catch (Throwable) {}
        }
        array_splice($household['profiles'], $index, 1);
        sf_household_save($household);
        json_response(['ok' => true, 'household' => sf_household_public($household)]);
    }

    json_response(['error' => 'Unknown household action.'], 422);
} catch (Throwable $e) {
    $code = (int)$e->getCode();
    $status = in_array($code, [401, 403, 404, 409, 422], true) ? $code : 500;
    json_response(['error' => $e->getMessage()], $status);
}

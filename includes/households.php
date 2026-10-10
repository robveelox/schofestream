<?php
declare(strict_types=1);

function sf_household_owner_id(): string
{
    return (string)($_SESSION['household_owner_user_id'] ?? '');
}

function sf_household_owner_name(): string
{
    return (string)($_SESSION['household_owner_username'] ?? '');
}

function sf_household_authenticated(): bool
{
    return sf_household_owner_id() !== '' && !empty($_SESSION['household_owner_token']);
}

function sf_profile_selected(): bool
{
    return !empty($_SESSION['active_profile_id']) && jf_user_id() !== '' && !empty($_SESSION['jf_token']);
}

function sf_profile_name(): string
{
    return (string)($_SESSION['active_profile_name'] ?? $_SESSION['jf_username'] ?? 'Profile');
}

function sf_profile_avatar_key(): string
{
    $key = (string)($_SESSION['active_profile_avatar'] ?? 'ocean');
    return in_array($key, sf_profile_avatar_keys(), true) ? $key : 'ocean';
}

function sf_profile_avatar_keys(): array
{
    return ['ocean', 'violet', 'mint', 'amber', 'rose', 'slate', 'sky', 'coral'];
}

function sf_households_dir(): string
{
    global $config;
    $dir = rtrim((string)($config['households_path'] ?? (__DIR__ . '/../storage/households')), '/');
    if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
        throw new RuntimeException('Schofestream household storage is not writable.');
    }
    return $dir;
}

function sf_household_file(?string $ownerId = null): string
{
    $ownerId ??= sf_household_owner_id();
    if ($ownerId === '') {
        throw new RuntimeException('No household owner session.');
    }
    return sf_households_dir() . '/' . hash('sha256', $ownerId) . '.json';
}

function sf_profile_secret_key(): string
{
    global $config;
    if (!extension_loaded('openssl')) {
        throw new RuntimeException('The OpenSSL PHP extension is required for household profiles.');
    }

    $path = (string)($config['profile_key_path'] ?? (dirname(sf_households_dir()) . '/profile.key'));
    $dir = dirname($path);
    if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
        throw new RuntimeException('Could not create the Schofestream data directory.');
    }

    if (!is_file($path)) {
        $key = random_bytes(32);
        if (@file_put_contents($path, base64_encode($key), LOCK_EX) === false) {
            throw new RuntimeException('Could not create the Schofestream profile encryption key.');
        }
        @chmod($path, 0600);
        return $key;
    }

    $raw = trim((string)@file_get_contents($path));
    $decoded = base64_decode($raw, true);
    if (!is_string($decoded) || strlen($decoded) !== 32) {
        throw new RuntimeException('The Schofestream profile encryption key is invalid.');
    }
    return $decoded;
}

function sf_encrypt_profile_token(string $token): array
{
    if ($token === '') {
        throw new RuntimeException('Cannot store an empty Jellyfin token.');
    }
    $cipher = 'aes-256-gcm';
    $iv = random_bytes(openssl_cipher_iv_length($cipher));
    $tag = '';
    $encrypted = openssl_encrypt($token, $cipher, sf_profile_secret_key(), OPENSSL_RAW_DATA, $iv, $tag, 'schofestream-profile-v1');
    if (!is_string($encrypted)) {
        throw new RuntimeException('Could not encrypt the Jellyfin profile session.');
    }
    return [
        'v' => 1,
        'ciphertext' => base64_encode($encrypted),
        'iv' => base64_encode($iv),
        'tag' => base64_encode($tag),
    ];
}

function sf_decrypt_profile_token(array $payload): string
{
    $ciphertext = base64_decode((string)($payload['ciphertext'] ?? ''), true);
    $iv = base64_decode((string)($payload['iv'] ?? ''), true);
    $tag = base64_decode((string)($payload['tag'] ?? ''), true);
    if (!is_string($ciphertext) || !is_string($iv) || !is_string($tag)) {
        throw new RuntimeException('Stored profile session data is invalid.');
    }
    $plain = openssl_decrypt($ciphertext, 'aes-256-gcm', sf_profile_secret_key(), OPENSSL_RAW_DATA, $iv, $tag, 'schofestream-profile-v1');
    if (!is_string($plain) || $plain === '') {
        throw new RuntimeException('Could not decrypt the stored Jellyfin profile session.');
    }
    return $plain;
}

function sf_new_profile_id(): string
{
    return 'prf_' . bin2hex(random_bytes(8));
}

function sf_sanitize_profile_name(string $name): string
{
    $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
    if ($name === '') {
        throw new RuntimeException('Enter a profile name.');
    }
    if (mb_strlen($name) > 28) {
        $name = mb_substr($name, 0, 28);
    }
    return $name;
}

function sf_sanitize_avatar_key(string $key): string
{
    return in_array($key, sf_profile_avatar_keys(), true) ? $key : 'ocean';
}

function sf_household_default(): array
{
    $ownerId = sf_household_owner_id();
    $ownerName = sf_household_owner_name() ?: 'Owner';
    if ($ownerId === '') {
        throw new RuntimeException('No household owner session.');
    }

    return [
        'version' => 1,
        'owner_user_id' => $ownerId,
        'name' => $ownerName . "'s Household",
        'created_at' => gmdate('c'),
        'updated_at' => gmdate('c'),
        'profiles' => [[
            'id' => sf_new_profile_id(),
            'name' => $ownerName,
            'jellyfin_user_id' => $ownerId,
            'jellyfin_username' => $ownerName,
            'avatar' => 'ocean',
            'is_owner' => true,
            'pin_hash' => null,
            'token' => null,
            'device_id' => null,
            'created_at' => gmdate('c'),
        ]],
    ];
}

function sf_household_load(bool $create = true): array
{
    $file = sf_household_file();
    if (!is_file($file)) {
        if (!$create) {
            return [];
        }
        $household = sf_household_default();
        sf_household_save($household);
        return $household;
    }

    $raw = @file_get_contents($file);
    $data = is_string($raw) ? json_decode($raw, true) : null;
    if (!is_array($data) || !is_array($data['profiles'] ?? null)) {
        throw new RuntimeException('The Schofestream household file is invalid.');
    }

    // Keep the owner mapping current if the Jellyfin display name changes.
    foreach ($data['profiles'] as &$profile) {
        if (!empty($profile['is_owner'])) {
            $profile['jellyfin_user_id'] = sf_household_owner_id();
            $profile['jellyfin_username'] = sf_household_owner_name();
            if (trim((string)($profile['name'] ?? '')) === '') {
                $profile['name'] = sf_household_owner_name();
            }
            $profile['token'] = null;
        }
    }
    unset($profile);

    return $data;
}

function sf_household_save(array $household): void
{
    $household['updated_at'] = gmdate('c');
    $file = sf_household_file((string)($household['owner_user_id'] ?? sf_household_owner_id()));
    $tmp = $file . '.' . bin2hex(random_bytes(5)) . '.tmp';
    $json = json_encode($household, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false || @file_put_contents($tmp, $json, LOCK_EX) === false) {
        @unlink($tmp);
        throw new RuntimeException('Could not save the Schofestream household. Check storage permissions.');
    }
    @chmod($tmp, 0640);
    if (!@rename($tmp, $file)) {
        @unlink($tmp);
        throw new RuntimeException('Could not finish saving the Schofestream household.');
    }
}

function sf_household_profile(array $household, string $profileId): ?array
{
    foreach ((array)($household['profiles'] ?? []) as $profile) {
        if (is_array($profile) && hash_equals((string)($profile['id'] ?? ''), $profileId)) {
            return $profile;
        }
    }
    return null;
}

function sf_household_public(array $household): array
{
    $active = (string)($_SESSION['active_profile_id'] ?? '');
    $profiles = [];
    foreach ((array)($household['profiles'] ?? []) as $profile) {
        if (!is_array($profile)) continue;
        $profiles[] = [
            'id' => (string)($profile['id'] ?? ''),
            'name' => (string)($profile['name'] ?? 'Profile'),
            'avatar' => sf_sanitize_avatar_key((string)($profile['avatar'] ?? 'ocean')),
            'jellyfinUsername' => (string)($profile['jellyfin_username'] ?? ''),
            'isOwner' => (bool)($profile['is_owner'] ?? false),
            'hasPin' => !empty($profile['pin_hash']),
            'needsRelink' => empty($profile['is_owner']) && empty($profile['token']),
            'active' => $active !== '' && hash_equals($active, (string)($profile['id'] ?? '')),
        ];
    }
    return [
        'name' => (string)($household['name'] ?? 'Household'),
        'profiles' => $profiles,
        'activeProfileId' => $active ?: null,
    ];
}

function sf_set_active_profile(array $profile): void
{
    $_SESSION['active_profile_id'] = (string)$profile['id'];
    $_SESSION['active_profile_name'] = (string)$profile['name'];
    $_SESSION['active_profile_avatar'] = sf_sanitize_avatar_key((string)($profile['avatar'] ?? 'ocean'));
    $_SESSION['jf_user_id'] = (string)$profile['jellyfin_user_id'];
    $_SESSION['jf_username'] = (string)$profile['jellyfin_username'];
    jf_cache_clear();
}

function sf_select_profile(array $profile, string $pin = ''): void
{
    $pinHash = (string)($profile['pin_hash'] ?? '');
    if ($pinHash !== '' && !password_verify($pin, $pinHash)) {
        throw new RuntimeException('Incorrect profile PIN.', 403);
    }

    $previous = [
        'token' => $_SESSION['jf_token'] ?? null,
        'user_id' => $_SESSION['jf_user_id'] ?? null,
        'username' => $_SESSION['jf_username'] ?? null,
        'profile_id' => $_SESSION['active_profile_id'] ?? null,
        'profile_name' => $_SESSION['active_profile_name'] ?? null,
        'profile_avatar' => $_SESSION['active_profile_avatar'] ?? null,
        'device_id' => $_SESSION['device_id'] ?? null,
    ];

    try {
        if (!empty($profile['is_owner'])) {
            $token = (string)($_SESSION['household_owner_token'] ?? '');
            $profileDeviceId = (string)($_SESSION['household_owner_device_id'] ?? $_SESSION['device_id'] ?? '');
            if ($token === '') {
                throw new RuntimeException('The household owner session has expired. Sign in again.');
            }
        } else {
            $tokenPayload = is_array($profile['token'] ?? null) ? $profile['token'] : [];
            if (!$tokenPayload) {
                throw new RuntimeException('This profile needs to be linked to Jellyfin again.', 409);
            }
            $token = sf_decrypt_profile_token($tokenPayload);
            $profileDeviceId = (string)($profile['device_id'] ?? $_SESSION['device_id'] ?? '');
        }

        if ($profileDeviceId !== '') $_SESSION['device_id'] = $profileDeviceId;
        $_SESSION['jf_token'] = $token;
        $_SESSION['jf_user_id'] = (string)$profile['jellyfin_user_id'];
        $_SESSION['jf_username'] = (string)$profile['jellyfin_username'];

        try {
            $user = jf_request('GET', '/Users/' . rawurlencode((string)$profile['jellyfin_user_id']));
        } catch (Throwable $e) {
            if (empty($profile['is_owner']) && (int)$e->getCode() === 401) {
                throw new RuntimeException('This profile link has expired. Relink the Jellyfin user in Manage Profiles.', 409);
            }
            throw $e;
        }
        if ((string)($user['Id'] ?? '') !== (string)$profile['jellyfin_user_id']) {
            throw new RuntimeException('Jellyfin returned the wrong linked profile.');
        }
        sf_set_active_profile($profile);
        session_regenerate_id(true);
    } catch (Throwable $e) {
        foreach ($previous as $key => $value) {
            $sessionKey = match ($key) {
                'token' => 'jf_token', 'user_id' => 'jf_user_id', 'username' => 'jf_username',
                'profile_id' => 'active_profile_id', 'profile_name' => 'active_profile_name',
                'profile_avatar' => 'active_profile_avatar', 'device_id' => 'device_id', default => $key,
            };
            if ($value === null) unset($_SESSION[$sessionKey]); else $_SESSION[$sessionKey] = $value;
        }
        throw $e;
    }
}

function sf_profile_current(): ?array
{
    if (!sf_household_authenticated()) return null;
    $active = (string)($_SESSION['active_profile_id'] ?? '');
    if ($active === '') return null;
    return sf_household_profile(sf_household_load(), $active);
}

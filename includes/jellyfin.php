<?php
declare(strict_types=1);

function jf_authorization_header(bool $withToken = true): string
{
    global $config;
    $parts = [
        'Client="' . addcslashes((string)$config['client_name'], '"\\') . '"',
        'Device="Web Browser"',
        'DeviceId="' . addcslashes((string)($_SESSION['device_id'] ?? ''), '"\\') . '"',
        'Version="' . addcslashes((string)$config['client_version'], '"\\') . '"',
    ];
    if ($withToken && !empty($_SESSION['jf_token'])) {
        $parts[] = 'Token="' . addcslashes((string)$_SESSION['jf_token'], '"\\') . '"';
    }
    return 'MediaBrowser ' . implode(', ', $parts);
}

function jf_request(
    string $method,
    string $path,
    array $query = [],
    ?array $body = null,
    bool $withToken = true,
    bool $raw = false
): array {
    global $config;

    $base = rtrim((string)$config['jellyfin_url'], '/');
    $url = $base . '/' . ltrim($path, '/');
    if ($query) {
        $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    $ch = curl_init($url);
    if ($ch === false) {
        throw new RuntimeException('Could not initialise the Jellyfin connection.');
    }

    $authorization = jf_authorization_header($withToken);
    $headers = [
        'Accept: application/json',
        'Authorization: ' . $authorization,
        'X-Emby-Authorization: ' . $authorization,
    ];

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => (int)$config['request_timeout'],
        CURLOPT_SSL_VERIFYPEER => (bool)$config['verify_ssl'],
        CURLOPT_SSL_VERIFYHOST => (bool)$config['verify_ssl'] ? 2 : 0,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_HEADER => false,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_ENCODING => '',
    ]);

    if ($body !== null) {
        $payload = json_encode($body, JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            curl_close($ch);
            throw new RuntimeException('Could not encode the Jellyfin request.');
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    $response = curl_exec($ch);
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException('Jellyfin connection failed: ' . $error);
    }

    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $contentType = (string)(curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: 'application/octet-stream');
    curl_close($ch);

    if ($raw) {
        return ['status' => $status, 'content_type' => $contentType, 'body' => $response];
    }

    $decoded = $response === '' ? [] : json_decode($response, true);
    if (!is_array($decoded)) {
        $decoded = ['raw' => $response];
    }

    if ($status < 200 || $status >= 300) {
        $message = $decoded['Message'] ?? $decoded['message'] ?? ('Jellyfin returned HTTP ' . $status);
        throw new RuntimeException((string)$message, $status);
    }

    return $decoded;
}

/**
 * Small per-session metadata cache. It never caches streams, credentials or mutations.
 * Keeping this in the PHP session makes it safe for shared hosting and user-specific.
 */
function jf_cached_request(string $path, array $query = [], ?int $ttl = null): array
{
    global $config;
    $ttl ??= max(0, (int)($config['metadata_cache_ttl'] ?? 20));
    if ($ttl <= 0) {
        return jf_request('GET', $path, $query);
    }

    $key = hash('sha256', jf_user_id() . '|' . $path . '|' . json_encode($query, JSON_UNESCAPED_SLASHES));
    $cache = is_array($_SESSION['jf_cache'] ?? null) ? $_SESSION['jf_cache'] : [];
    $entry = $cache[$key] ?? null;
    if (is_array($entry) && (int)($entry['expires'] ?? 0) >= time() && is_array($entry['value'] ?? null)) {
        return $entry['value'];
    }

    $value = jf_request('GET', $path, $query);
    $cache[$key] = ['expires' => time() + $ttl, 'value' => $value];
    if (count($cache) > 40) {
        uasort($cache, static fn(array $a, array $b): int => (int)($a['expires'] ?? 0) <=> (int)($b['expires'] ?? 0));
        $cache = array_slice($cache, -40, null, true);
    }
    $_SESSION['jf_cache'] = $cache;
    return $value;
}

function jf_cache_clear(): void
{
    unset($_SESSION['jf_cache']);
}

function jf_user_id(): string
{
    return (string)($_SESSION['jf_user_id'] ?? '');
}

function jf_image_proxy_url(string $id, string $type = 'Primary', int $width = 720): string
{
    return '/api/image.php?' . http_build_query([
        'id' => $id,
        'type' => $type,
        'width' => $width,
    ]);
}

function jf_item_fields(): string
{
    return 'Overview,Genres,PrimaryImageAspectRatio,RunTimeTicks,ProductionYear,CommunityRating,OfficialRating,DateCreated,People,Studios,Taglines,PremiereDate';
}

function jf_normalize_item(array $item): array
{
    $userData = is_array($item['UserData'] ?? null) ? $item['UserData'] : [];
    $type = (string)($item['Type'] ?? '');
    $id = (string)($item['Id'] ?? '');
    $seriesId = (string)($item['SeriesId'] ?? '');

    $imageTags = is_array($item['ImageTags'] ?? null) ? $item['ImageTags'] : [];
    $hasPrimary = !empty($imageTags['Primary']) || !empty($item['PrimaryImageTag']);
    $hasBackdrop = !empty($item['BackdropImageTags']);
    $backdropId = $id;
    if (!$hasBackdrop && $type === 'Episode' && $seriesId !== '') {
        $backdropId = $seriesId;
        $hasBackdrop = true;
    }

    $studios = array_values(array_filter(array_map(
        static fn($studio) => is_array($studio) ? trim((string)($studio['Name'] ?? '')) : '',
        (array)($item['Studios'] ?? [])
    )));

    $name = trim((string)($item['Name'] ?? ''));
    if ($name === '') $name = 'Untitled';

    return [
        'id' => $id,
        'name' => $name,
        'type' => $type,
        'overview' => trim((string)($item['Overview'] ?? '')),
        'year' => $item['ProductionYear'] ?? null,
        'runtimeSeconds' => ticks_to_seconds($item['RunTimeTicks'] ?? 0),
        'positionSeconds' => ticks_to_seconds($userData['PlaybackPositionTicks'] ?? 0),
        'playedPercentage' => isset($userData['PlayedPercentage']) ? (float)$userData['PlayedPercentage'] : null,
        'played' => (bool)($userData['Played'] ?? false),
        'favorite' => (bool)($userData['IsFavorite'] ?? false),
        'playCount' => (int)($userData['PlayCount'] ?? 0),
        'lastPlayedDate' => $userData['LastPlayedDate'] ?? null,
        'genres' => array_values(array_filter(array_map('strval', (array)($item['Genres'] ?? [])))),
        'communityRating' => is_numeric($item['CommunityRating'] ?? null) ? (float)$item['CommunityRating'] : null,
        'officialRating' => $item['OfficialRating'] ?? null,
        'seriesId' => $item['SeriesId'] ?? null,
        'seriesName' => $item['SeriesName'] ?? null,
        'seasonName' => $item['SeasonName'] ?? null,
        'indexNumber' => $item['IndexNumber'] ?? null,
        'parentIndexNumber' => $item['ParentIndexNumber'] ?? null,
        'dateCreated' => $item['DateCreated'] ?? null,
        'premiereDate' => $item['PremiereDate'] ?? null,
        'studios' => $studios,
        'poster' => ($id !== '' && $hasPrimary) ? jf_image_proxy_url($id, 'Primary', 360) : null,
        'thumb' => ($id !== '' && $hasPrimary) ? jf_image_proxy_url($id, 'Primary', 640) : null,
        'backdrop' => ($hasBackdrop && $backdropId !== '') ? jf_image_proxy_url($backdropId, 'Backdrop', 1280) : null,
    ];
}

function jf_normalize_items(array $items): array
{
    return array_values(array_map('jf_normalize_item', array_values(array_filter($items, 'is_array'))));
}

<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_auth_api();

$userId = jf_user_id();
try {
    $user = jf_cached_request("/Users/{$userId}", [], 30);
    $history = jf_cached_request("/Users/{$userId}/Items", [
        'IncludeItemTypes' => 'Movie,Episode', 'Recursive' => 'true', 'Limit' => 50,
        'SortBy' => 'DatePlayed', 'SortOrder' => 'Descending', 'Fields' => jf_item_fields(),
        'EnableImages' => 'true', 'ImageTypeLimit' => 1, 'EnableUserData' => 'true',
        'EnableTotalRecordCount' => 'false',
    ], 20);
    $recent = array_values(array_filter(jf_normalize_items((array)($history['Items'] ?? [])), static fn(array $i): bool => !empty($i['lastPlayedDate'])));

    $favorites = jf_cached_request("/Users/{$userId}/Items", [
        'IncludeItemTypes' => 'Movie,Series', 'Recursive' => 'true', 'Filters' => 'IsFavorite',
        'Limit' => 1, 'EnableUserData' => 'true', 'EnableTotalRecordCount' => 'true'
    ], 30);

    $moviePlays = count(array_filter($recent, static fn(array $i): bool => $i['type'] === 'Movie'));
    $episodePlays = count(array_filter($recent, static fn(array $i): bool => $i['type'] === 'Episode'));
    $configData = is_array($user['Configuration'] ?? null) ? $user['Configuration'] : [];

    json_response([
        'user' => [
            'id' => $userId,
            'name' => (string)($user['Name'] ?? ($_SESSION['jf_username'] ?? 'User')),
            'avatar' => '/api/avatar.php',
            'hasPassword' => (bool)($user['HasPassword'] ?? false),
            'lastLoginDate' => $user['LastLoginDate'] ?? null,
            'lastActivityDate' => $user['LastActivityDate'] ?? null,
            'audioLanguage' => $configData['AudioLanguagePreference'] ?? null,
            'subtitleLanguage' => $configData['SubtitleLanguagePreference'] ?? null,
        ],
        'stats' => [
            'recentMovies' => $moviePlays,
            'recentEpisodes' => $episodePlays,
            'favorites' => (int)($favorites['TotalRecordCount'] ?? 0),
        ],
        'recent' => array_slice($recent, 0, 10),
        'preferences' => sf_preferences(),
        'session' => [
            'deviceId' => substr((string)($_SESSION['device_id'] ?? ''), 0, 8) . '…',
            'signedInAt' => $_SESSION['login_at'] ?? null,
        ],
    ]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 502);
}

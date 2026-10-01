<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_auth_api();

$q = trim((string)($_GET['q'] ?? ''));
$compact = ($_GET['compact'] ?? '') === '1';
$limit = max(4, min(30, (int)($_GET['limit'] ?? ($compact ? ($config['instant_search_limit'] ?? 8) : 20))));
if (strlen($q) < 2) {
    json_response(['items' => [], 'movies' => [], 'shows' => [], 'episodes' => [], 'people' => []]);
}
$userId = jf_user_id();

try {
    $result = jf_request('GET', "/Users/{$userId}/Items", [
        'SearchTerm' => $q,
        'IncludeItemTypes' => 'Movie,Series,Episode',
        'Recursive' => 'true',
        'Limit' => $compact ? ($limit * 3) : 80,
        'Fields' => jf_item_fields(),
        'EnableImages' => 'true',
        'ImageTypeLimit' => 1,
        'EnableUserData' => 'true',
        'EnableTotalRecordCount' => 'false',
    ]);
    $items = jf_normalize_items((array)($result['Items'] ?? []));

    $people = [];
    try {
        $personResult = jf_request('GET', '/Persons', [
            'SearchTerm' => $q,
            'UserId' => $userId,
            'Limit' => $compact ? 4 : 16,
            'EnableImages' => 'true',
            'ImageTypeLimit' => 1,
            'EnableTotalRecordCount' => 'false',
        ]);
        $people = array_values(array_filter(array_map(static function ($person): ?array {
            if (!is_array($person)) return null;
            $name = trim((string)($person['Name'] ?? ''));
            $id = trim((string)($person['Id'] ?? ''));
            if ($name === '') return null;
            $hasPrimary = !empty($person['ImageTags']['Primary']) || !empty($person['PrimaryImageTag']);
            return [
                'id' => $id,
                'name' => $name,
                'type' => 'Person',
                'image' => ($id !== '' && $hasPrimary) ? jf_image_proxy_url($id, 'Primary', 220) : null,
            ];
        }, (array)($personResult['Items'] ?? []))));
    } catch (Throwable) {
        // Title search remains useful even if Jellyfin's people endpoint is unavailable.
    }

    $movies = array_values(array_filter($items, static fn($i) => ($i['type'] ?? '') === 'Movie'));
    $shows = array_values(array_filter($items, static fn($i) => ($i['type'] ?? '') === 'Series'));
    $episodes = array_values(array_filter($items, static fn($i) => ($i['type'] ?? '') === 'Episode'));
    if ($compact) {
        $movies = array_slice($movies, 0, $limit);
        $shows = array_slice($shows, 0, $limit);
        $episodes = array_slice($episodes, 0, $limit);
    }
    json_response(['items' => $items, 'movies' => $movies, 'shows' => $shows, 'episodes' => $episodes, 'people' => $people]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 502);
}

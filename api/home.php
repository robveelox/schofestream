<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_auth_api();

$userId = jf_user_id();
$fields = jf_item_fields();
$limit = max(8, min(30, (int)($config['home_row_limit'] ?? 18)));
$warnings = [];

$safe = static function (string $label, string $path, array $query = [], int $ttl = 20) use (&$warnings): array {
    try {
        return jf_cached_request($path, $query, $ttl);
    } catch (Throwable $e) {
        $warnings[] = $label;
        return [];
    }
};

$continueCandidates = $safe('Continue Watching', "/Users/{$userId}/Items", [
    'IncludeItemTypes' => 'Movie,Episode',
    'Recursive' => 'true',
    'IsPlayed' => 'false',
    'Limit' => 120,
    'SortBy' => 'DatePlayed',
    'SortOrder' => 'Descending',
    'Fields' => $fields,
    'EnableImages' => 'true',
    'ImageTypeLimit' => 1,
    'EnableUserData' => 'true',
    'EnableTotalRecordCount' => 'false',
]);

$nextUp = $safe('Next Up', '/Shows/NextUp', [
    'UserId' => $userId,
    'Limit' => $limit,
    'Fields' => $fields,
    'EnableImages' => 'true',
    'ImageTypeLimit' => 1,
    'EnableUserData' => 'true',
    'EnableResumable' => 'true',
    'EnableTotalRecordCount' => 'false',
]);

$latestMovies = $safe('Recently Added Movies', "/Users/{$userId}/Items/Latest", [
    'Limit' => $limit,
    'IncludeItemTypes' => 'Movie',
    'Fields' => $fields,
    'EnableImages' => 'true',
    'ImageTypeLimit' => 1,
    'EnableUserData' => 'true',
]);

$latestShows = $safe('Recently Added TV', "/Users/{$userId}/Items/Latest", [
    'Limit' => $limit,
    'IncludeItemTypes' => 'Series',
    'Fields' => $fields,
    'EnableImages' => 'true',
    'ImageTypeLimit' => 1,
    'EnableUserData' => 'true',
]);

$favorites = $safe('My List', "/Users/{$userId}/Items", [
    'IncludeItemTypes' => 'Movie,Series',
    'Recursive' => 'true',
    'Filters' => 'IsFavorite',
    'Limit' => $limit,
    'SortBy' => 'DatePlayed,SortName',
    'SortOrder' => 'Descending',
    'Fields' => $fields,
    'EnableImages' => 'true',
    'ImageTypeLimit' => 1,
    'EnableUserData' => 'true',
    'EnableTotalRecordCount' => 'false',
]);

$collections = $safe('Collections', "/Users/{$userId}/Items", [
    'IncludeItemTypes' => 'BoxSet',
    'Recursive' => 'true',
    'Limit' => 12,
    'SortBy' => 'SortName',
    'SortOrder' => 'Ascending',
    'Fields' => $fields,
    'EnableImages' => 'true',
    'ImageTypeLimit' => 1,
    'EnableUserData' => 'true',
    'EnableTotalRecordCount' => 'false',
], 45);

$genreResult = $safe('Genres', '/Genres', [
    'UserId' => $userId,
    'IncludeItemTypes' => 'Movie,Series',
    'Limit' => 12,
    'SortBy' => 'SortName',
    'SortOrder' => 'Ascending',
    'EnableImages' => 'false',
    'EnableTotalRecordCount' => 'false',
], 60);

$normalizeLatest = static fn(array $result): array => jf_normalize_items(array_is_list($result) ? $result : (array)($result['Items'] ?? []));

$resumeItems = array_values(array_filter(
    jf_normalize_items((array)($continueCandidates['Items'] ?? [])),
    static fn(array $item): bool => (float)($item['positionSeconds'] ?? 0) > 0 && empty($item['played'])
));
$resumeItems = array_slice($resumeItems, 0, $limit);

$movieItems = $normalizeLatest($latestMovies);
$showItems = $normalizeLatest($latestShows);
$nextItems = jf_normalize_items((array)($nextUp['Items'] ?? []));
$favoriteItems = jf_normalize_items((array)($favorites['Items'] ?? []));
$collectionItems = jf_normalize_items((array)($collections['Items'] ?? []));

$genres = array_values(array_filter(array_map(static function ($genre): ?array {
    $name = trim((string)($genre['Name'] ?? ''));
    return $name === '' ? null : ['id' => (string)($genre['Id'] ?? ''), 'name' => $name];
}, (array)($genreResult['Items'] ?? []))));

$genreRows = [];
foreach (array_slice($genres, 0, 2) as $genre) {
    $result = $safe('Genre ' . $genre['name'], "/Users/{$userId}/Items", [
        'IncludeItemTypes' => 'Movie,Series',
        'Recursive' => 'true',
        'Genres' => $genre['name'],
        'Limit' => $limit,
        'SortBy' => 'Random',
        'Fields' => $fields,
        'EnableImages' => 'true',
        'ImageTypeLimit' => 1,
        'EnableUserData' => 'true',
        'EnableTotalRecordCount' => 'false',
    ], 30);
    $items = jf_normalize_items((array)($result['Items'] ?? []));
    if ($items) $genreRows[] = ['name' => $genre['name'], 'items' => $items];
}

// Prefer an unwatched, recently-added movie/series with backdrop artwork. Avoid episodes as heroes.
$heroCandidates = array_merge($movieItems, $showItems, $favoriteItems);
$hero = null;
foreach ($heroCandidates as $candidate) {
    if (!empty($candidate['backdrop']) && empty($candidate['played'])) {
        $hero = $candidate;
        break;
    }
}
if (!$hero) {
    foreach ($heroCandidates as $candidate) {
        if (!empty($candidate['backdrop'])) {
            $hero = $candidate;
            break;
        }
    }
}
$hero ??= ($movieItems[0] ?? $showItems[0] ?? null);

json_response([
    'hero' => $hero,
    'continueWatching' => $resumeItems,
    'nextUp' => $nextItems,
    'recentMovies' => $movieItems,
    'recentShows' => $showItems,
    'favorites' => $favoriteItems,
    'collections' => $collectionItems,
    'genres' => $genres,
    'genreRows' => $genreRows,
    'partial' => !empty($warnings),
    'unavailableRows' => array_values(array_unique($warnings)),
]);

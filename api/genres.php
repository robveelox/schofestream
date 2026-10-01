<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_auth_api();
$userId = jf_user_id();
$name = trim((string)($_GET['name'] ?? ''));
$type = ($_GET['type'] ?? '') === 'Movie' ? 'Movie' : (($_GET['type'] ?? '') === 'Series' ? 'Series' : 'Movie,Series');

try {
    if ($name === '') {
        $result = jf_cached_request( '/Genres', [
            'UserId' => $userId,
            'IncludeItemTypes' => 'Movie,Series',
            'Limit' => 200,
            'SortBy' => 'SortName',
            'SortOrder' => 'Ascending',
            'EnableImages' => 'false',
        ]);
        $genres = array_values(array_filter(array_map(static function ($genre): ?array {
            $name = trim((string)($genre['Name'] ?? ''));
            return $name === '' ? null : ['id' => (string)($genre['Id'] ?? ''), 'name' => $name];
        }, (array)($result['Items'] ?? []))));
        json_response(['genres' => $genres]);
    }

    $result = jf_request('GET', "/Users/{$userId}/Items", [
        'IncludeItemTypes' => $type,
        'Recursive' => 'true',
        'Genres' => $name,
        'Limit' => 500,
        'SortBy' => 'SortName',
        'SortOrder' => 'Ascending',
        'Fields' => jf_item_fields(),
        'EnableImages' => 'true',
        'ImageTypeLimit' => 1,
        'EnableUserData' => 'true',
    ]);
    json_response(['name' => $name, 'items' => jf_normalize_items((array)($result['Items'] ?? []))]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 502);
}

<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_auth_api();

$type = ($_GET['type'] ?? 'Movie') === 'Series' ? 'Series' : 'Movie';
$sort = (string)($_GET['sort'] ?? 'title');
$genre = trim((string)($_GET['genre'] ?? ''));
$sortMap = [
    'title' => ['SortName', 'Ascending'],
    'recent' => ['DateCreated', 'Descending'],
    'year' => ['ProductionYear,SortName', 'Descending'],
    'rating' => ['CommunityRating,SortName', 'Descending'],
];
[$sortBy, $sortOrder] = $sortMap[$sort] ?? $sortMap['title'];
$userId = jf_user_id();

try {
    $query = [
        'IncludeItemTypes' => $type,
        'Recursive' => 'true',
        'SortBy' => $sortBy,
        'SortOrder' => $sortOrder,
        'Limit' => 500,
        'Fields' => jf_item_fields(),
        'EnableImages' => 'true',
        'ImageTypeLimit' => 1,
        'EnableUserData' => 'true',
    ];
    if ($genre !== '') {
        $query['Genres'] = $genre;
    }
    $result = jf_request('GET', "/Users/{$userId}/Items", $query);
    $items = jf_normalize_items((array)($result['Items'] ?? []));
    json_response(['items' => $items, 'total' => (int)($result['TotalRecordCount'] ?? count($items))]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 502);
}

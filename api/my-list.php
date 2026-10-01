<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_auth_api();

$userId = jf_user_id();
$sortKey = (string)($_GET['sort'] ?? 'title');
$sorts = [
    'title' => ['SortName', 'Ascending'],
    'added' => ['DateCreated', 'Descending'],
    'year' => ['ProductionYear,SortName', 'Descending'],
    'watched' => ['DatePlayed,SortName', 'Descending'],
];
[$sortBy, $sortOrder] = $sorts[$sortKey] ?? $sorts['title'];

try {
    $result = jf_request('GET',"/Users/{$userId}/Items", [
        'IncludeItemTypes' => 'Movie,Series',
        'Recursive' => 'true',
        'Filters' => 'IsFavorite',
        'SortBy' => $sortBy,
        'SortOrder' => $sortOrder,
        'Limit' => 500,
        'Fields' => jf_item_fields(),
        'EnableImages' => 'true',
        'ImageTypeLimit' => 1,
        'EnableUserData' => 'true',
    ]);
    $items = jf_normalize_items((array)($result['Items'] ?? []));
    json_response(['items' => $items, 'total' => (int)($result['TotalRecordCount'] ?? count($items)), 'sort' => $sortKey]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 502);
}

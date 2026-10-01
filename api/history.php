<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_auth_api();
$userId = jf_user_id();
$type = (string)($_GET['type'] ?? 'All');
$allowed = ['All','Movie','Episode'];
if (!in_array($type, $allowed, true)) $type = 'All';
$limit = max(10, min(150, (int)($_GET['limit'] ?? ($config['history_limit'] ?? 80))));
try {
    $result = jf_cached_request("/Users/{$userId}/Items", [
        'IncludeItemTypes' => $type === 'All' ? 'Movie,Episode' : $type,
        'Recursive' => 'true', 'Limit' => min(250, $limit * 3),
        'SortBy' => 'DatePlayed', 'SortOrder' => 'Descending', 'Fields' => jf_item_fields(),
        'EnableImages' => 'true', 'ImageTypeLimit' => 1, 'EnableUserData' => 'true', 'EnableTotalRecordCount' => 'false',
    ], 20);
    $items = array_values(array_filter(jf_normalize_items((array)($result['Items'] ?? [])), static fn(array $item): bool => !empty($item['lastPlayedDate'])));
    usort($items, static fn(array $a, array $b): int => strcmp((string)($b['lastPlayedDate'] ?? ''), (string)($a['lastPlayedDate'] ?? '')));
    json_response(['items' => array_slice($items, 0, $limit)]);
} catch (Throwable $e) { json_response(['error' => $e->getMessage()], 502); }

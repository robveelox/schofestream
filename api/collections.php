<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_auth_api();
$userId = jf_user_id();
try {
    $result = jf_request('GET', "/Users/{$userId}/Items", [
        'IncludeItemTypes' => 'BoxSet',
        'Recursive' => 'true',
        'SortBy' => 'SortName',
        'SortOrder' => 'Ascending',
        'Limit' => 500,
        'Fields' => jf_item_fields(),
        'EnableImages' => 'true',
        'ImageTypeLimit' => 1,
        'EnableUserData' => 'true',
    ]);
    json_response(['items' => jf_normalize_items((array)($result['Items'] ?? []))]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 502);
}

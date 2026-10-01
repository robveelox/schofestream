<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_auth_api();

$seriesId = trim((string)($_GET['seriesId'] ?? ''));
$seasonId = trim((string)($_GET['seasonId'] ?? ''));
if ($seriesId === '' || $seasonId === '') json_response(['error' => 'Missing series or season id'], 422);

try {
    $result = jf_cached_request("/Shows/{$seriesId}/Episodes", [
        'UserId' => jf_user_id(),
        'SeasonId' => $seasonId,
        'Fields' => jf_item_fields(),
        'EnableImages' => 'true',
        'ImageTypeLimit' => 1,
        'EnableUserData' => 'true',
    ], 15);
    json_response(['items' => jf_normalize_items((array)($result['Items'] ?? []))]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 502);
}

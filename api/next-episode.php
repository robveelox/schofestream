<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_auth_api();
$id = trim((string)($_GET['id'] ?? ''));
if ($id === '') {
    json_response(['error' => 'Missing item id'], 422);
}
$userId = jf_user_id();

try {
    $current = jf_request('GET', "/Users/{$userId}/Items/{$id}");
    $seriesId = trim((string)($current['SeriesId'] ?? ''));
    if (($current['Type'] ?? '') !== 'Episode' || $seriesId === '') {
        json_response(['next' => null]);
    }

    $result = jf_request('GET', "/Shows/{$seriesId}/Episodes", [
        'UserId' => $userId,
        'Fields' => jf_item_fields(),
        'EnableImages' => 'true',
        'ImageTypeLimit' => 1,
        'EnableUserData' => 'true',
        'Limit' => 1000,
    ]);
    $items = array_values(array_filter((array)($result['Items'] ?? []), 'is_array'));
    usort($items, static function (array $a, array $b): int {
        $sa = (int)($a['ParentIndexNumber'] ?? 0);
        $sb = (int)($b['ParentIndexNumber'] ?? 0);
        if ($sa !== $sb) return $sa <=> $sb;
        return (int)($a['IndexNumber'] ?? 0) <=> (int)($b['IndexNumber'] ?? 0);
    });
    $found = false;
    foreach ($items as $episode) {
        if (!is_array($episode)) continue;
        if ($found) {
            json_response(['next' => jf_normalize_item($episode)]);
        }
        if ((string)($episode['Id'] ?? '') === $id) {
            $found = true;
        }
    }
    json_response(['next' => null]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 502);
}

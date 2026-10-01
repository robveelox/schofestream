<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_auth_api();

$id = trim((string)($_GET['id'] ?? ''));
if ($id === '') json_response(['error' => 'Missing item id'], 422);
$userId = jf_user_id();

try {
    $raw = jf_cached_request("/Users/{$userId}/Items/{$id}", [], 20);
} catch (Throwable $e) {
    $code = $e->getCode() === 404 ? 404 : 502;
    json_response(['error' => $e->getMessage()], $code);
}

$item = jf_normalize_item($raw);
$item['people'] = array_values(array_filter(array_map(static function ($p): ?array {
    if (!is_array($p)) return null;
    $name = trim((string)($p['Name'] ?? ''));
    if ($name === '') return null;
    $pid = trim((string)($p['Id'] ?? ''));
    return [
        'id' => $pid,
        'name' => $name,
        'role' => trim((string)($p['Role'] ?? '')),
        'type' => trim((string)($p['Type'] ?? '')),
        'image' => $pid !== '' ? jf_image_proxy_url($pid, 'Primary', 220) : null,
    ];
}, (array)($raw['People'] ?? []))));
$item['taglines'] = array_values(array_filter(array_map('strval', (array)($raw['Taglines'] ?? []))));
$item['mediaType'] = $raw['MediaType'] ?? null;
$item['studios'] = array_values(array_filter(array_map(static fn($s) => is_array($s) ? trim((string)($s['Name'] ?? '')) : '', (array)($raw['Studios'] ?? []))));

$seasons = [];
$seriesPlay = null;
$children = [];
$similar = [];
$warnings = [];
$type = (string)($raw['Type'] ?? '');

if ($type === 'Series') {
    try {
        $seasonResult = jf_cached_request("/Shows/{$id}/Seasons", [
            'UserId' => $userId,
            'Fields' => 'Overview,PrimaryImageAspectRatio',
            'EnableImages' => 'true',
            'ImageTypeLimit' => 1,
            'EnableUserData' => 'true',
        ], 30);
        $seasons = array_values(array_filter(array_map(static function ($season): ?array {
            if (!is_array($season)) return null;
            $sid = trim((string)($season['Id'] ?? ''));
            if ($sid === '') return null;
            return [
                'id' => $sid,
                'name' => trim((string)($season['Name'] ?? 'Season')) ?: 'Season',
                'indexNumber' => $season['IndexNumber'] ?? null,
            ];
        }, (array)($seasonResult['Items'] ?? []))));
    } catch (Throwable) {
        $warnings[] = 'Seasons are temporarily unavailable.';
    }

    try {
        $next = jf_request('GET', '/Shows/NextUp', [
            'UserId' => $userId,
            'SeriesId' => $id,
            'Limit' => 1,
            'Fields' => jf_item_fields(),
            'EnableImages' => 'true',
            'ImageTypeLimit' => 1,
            'EnableUserData' => 'true',
            'EnableResumable' => 'true',
            'EnableTotalRecordCount' => 'false',
        ]);
        if (!empty($next['Items'][0]) && is_array($next['Items'][0])) {
            $seriesPlay = jf_normalize_item($next['Items'][0]);
        }
    } catch (Throwable) {
        // Fall through to first episode lookup below.
    }

    if (!$seriesPlay) {
        try {
            $episodeResult = jf_cached_request("/Shows/{$id}/Episodes", [
                'UserId' => $userId,
                'Limit' => 1,
                'Fields' => jf_item_fields(),
                'EnableImages' => 'true',
                'ImageTypeLimit' => 1,
                'EnableUserData' => 'true',
            ], 20);
            if (!empty($episodeResult['Items'][0]) && is_array($episodeResult['Items'][0])) {
                $seriesPlay = jf_normalize_item($episodeResult['Items'][0]);
            }
        } catch (Throwable) {
            $warnings[] = 'The next episode could not be loaded.';
        }
    }
}

if ($type === 'BoxSet') {
    try {
        $childResult = jf_cached_request("/Users/{$userId}/Items", [
            'ParentId' => $id,
            'Recursive' => 'true',
            'IncludeItemTypes' => 'Movie,Series',
            'Limit' => 200,
            'SortBy' => 'ProductionYear,SortName',
            'SortOrder' => 'Ascending',
            'Fields' => jf_item_fields(),
            'EnableImages' => 'true',
            'ImageTypeLimit' => 1,
            'EnableUserData' => 'true',
        ], 30);
        $children = jf_normalize_items((array)($childResult['Items'] ?? []));
    } catch (Throwable) {
        $warnings[] = 'Collection titles are temporarily unavailable.';
    }
}

try {
    $similarResult = jf_cached_request("/Items/{$id}/Similar", [
        'UserId' => $userId,
        'Limit' => 16,
        'Fields' => jf_item_fields(),
    ], 45);
    $similar = jf_normalize_items((array)($similarResult['Items'] ?? []));
} catch (Throwable) {
    // Similar titles are optional; details should remain usable without them.
}

json_response([
    'item' => $item,
    'seasons' => $seasons,
    'seriesPlay' => $seriesPlay,
    'children' => $children,
    'similar' => $similar,
    'warnings' => $warnings,
]);

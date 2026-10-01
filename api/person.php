<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_auth_api();
$name = trim((string)($_GET['name'] ?? ''));
if ($name === '') {
    json_response(['error' => 'Missing person name'], 422);
}
$userId = jf_user_id();
try {
    $person = jf_cached_request( '/Persons/' . rawurlencode($name), ['UserId' => $userId]);
    $personId = (string)($person['Id'] ?? '');
    $items = [];
    if ($personId !== '') {
        $result = jf_request('GET', "/Users/{$userId}/Items", [
            'PersonIds' => $personId,
            'IncludeItemTypes' => 'Movie,Series',
            'Recursive' => 'true',
            'Limit' => 200,
            'SortBy' => 'ProductionYear,SortName',
            'SortOrder' => 'Descending',
            'Fields' => jf_item_fields(),
            'EnableImages' => 'true',
            'ImageTypeLimit' => 1,
            'EnableUserData' => 'true',
        ]);
        $items = jf_normalize_items((array)($result['Items'] ?? []));
    }
    json_response([
        'person' => [
            'id' => $personId,
            'name' => (string)($person['Name'] ?? $name),
            'overview' => (string)($person['Overview'] ?? ''),
            'image' => $personId !== '' ? jf_image_proxy_url($personId, 'Primary', 600) : null,
        ],
        'items' => $items,
    ]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], $e->getCode() === 404 ? 404 : 502);
}

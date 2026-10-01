<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_auth_api();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}
$data = json_input();
verify_csrf((string)($data['csrf'] ?? ''));

$event = (string)($data['event'] ?? 'progress');
$paths = [
    'start' => '/Sessions/Playing',
    'progress' => '/Sessions/Playing/Progress',
    'stop' => '/Sessions/Playing/Stopped',
];
if (!isset($paths[$event])) {
    json_response(['error' => 'Invalid playback event'], 422);
}

$itemId = trim((string)($data['itemId'] ?? ''));
if ($itemId === '') {
    json_response(['error' => 'Missing item id'], 422);
}

$positionTicks = max(0, (int)round(((float)($data['positionSeconds'] ?? 0)) * 10_000_000));
$payload = [
    'ItemId' => $itemId,
    'MediaSourceId' => (string)($data['mediaSourceId'] ?? ''),
    'PlaySessionId' => (string)($data['playSessionId'] ?? ''),
    'PositionTicks' => $positionTicks,
    'IsPaused' => (bool)($data['isPaused'] ?? false),
    'IsMuted' => (bool)($data['isMuted'] ?? false),
    'CanSeek' => true,
    'PlayMethod' => (string)($data['playMethod'] ?? 'Transcode'),
    'VolumeLevel' => max(0, min(100, (int)($data['volumeLevel'] ?? 100))),
    'AudioStreamIndex' => isset($data['audioStreamIndex']) && $data['audioStreamIndex'] !== null ? (int)$data['audioStreamIndex'] : null,
    'SubtitleStreamIndex' => isset($data['subtitleStreamIndex']) ? (int)$data['subtitleStreamIndex'] : -1,
];

try {
    jf_request('POST', $paths[$event], [], $payload);
    if ($event === 'stop') jf_cache_clear();
    json_response(['ok' => true]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 502);
}

<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_auth_api();

$id = trim((string)($_GET['id'] ?? ''));
if ($id === '') json_response(['error' => 'Missing item id'], 422);

$userId = jf_user_id();
$serverMax = (int)$config['max_streaming_bitrate'];
$requestedBitrate = max(0, (int)($_GET['bitrate'] ?? 0));
$maxBitrate = $requestedBitrate > 0 ? min($serverMax, max(1_000_000, $requestedBitrate)) : $serverMax;
$audioIndex = isset($_GET['audio']) && $_GET['audio'] !== '' ? max(0, (int)$_GET['audio']) : null;
$subtitleIndex = isset($_GET['subtitle']) && $_GET['subtitle'] !== '' ? (int)$_GET['subtitle'] : null;
if ($subtitleIndex !== null && $subtitleIndex < -1) $subtitleIndex = -1;
$positionOverride = max(0.0, (float)($_GET['position'] ?? 0));

$deviceProfile = [
    'Name' => 'Schofestream Web HLS',
    'MaxStreamingBitrate' => $maxBitrate,
    'MaxStaticBitrate' => $maxBitrate,
    'MusicStreamingTranscodingBitrate' => 192000,
    'DirectPlayProfiles' => [],
    'TranscodingProfiles' => [[
        'Container' => 'ts',
        'Type' => 'Video',
        'VideoCodec' => 'h264',
        'AudioCodec' => 'aac',
        'Protocol' => 'hls',
        'Context' => 'Streaming',
        'EstimateContentLength' => false,
        'EnableMpegtsM2TsMode' => false,
        'TranscodeSeekInfo' => 'Auto',
        'CopyTimestamps' => false,
        'EnableSubtitlesInManifest' => false,
        'MaxAudioChannels' => '2',
        'MinSegments' => 1,
        'SegmentLength' => 0,
        'BreakOnNonKeyFrames' => true,
    ]],
    'ContainerProfiles' => [],
    'CodecProfiles' => [[
        'Type' => 'Video',
        'Codec' => 'h264',
        'Conditions' => [
            ['Condition' => 'NotEquals', 'Property' => 'IsAnamorphic', 'Value' => 'true', 'IsRequired' => false],
            ['Condition' => 'EqualsAny', 'Property' => 'VideoProfile', 'Value' => 'high|main|baseline|constrained baseline', 'IsRequired' => false],
            ['Condition' => 'LessThanEqual', 'Property' => 'VideoLevel', 'Value' => '51', 'IsRequired' => false],
        ],
        'ApplyConditions' => [],
    ]],
    'SubtitleProfiles' => [
        ['Format' => 'vtt', 'Method' => 'External'],
        ['Format' => 'srt', 'Method' => 'External'],
        ['Format' => 'ass', 'Method' => 'Encode'],
        ['Format' => 'ssa', 'Method' => 'Encode'],
        ['Format' => 'subrip', 'Method' => 'External'],
    ],
];

try {
    $itemRaw = jf_request('GET', "/Users/{$userId}/Items/{$id}");
    $item = jf_normalize_item($itemRaw);

    $playbackRequest = [
        'UserId' => $userId,
        'MaxStreamingBitrate' => $maxBitrate,
        'MaxAudioChannels' => 2,
        'EnableDirectPlay' => false,
        'EnableDirectStream' => false,
        'EnableTranscoding' => true,
        'AllowVideoStreamCopy' => true,
        'AllowAudioStreamCopy' => true,
        'DeviceProfile' => $deviceProfile,
    ];
    if ($audioIndex !== null) $playbackRequest['AudioStreamIndex'] = $audioIndex;
    if ($subtitleIndex !== null) {
        $playbackRequest['SubtitleStreamIndex'] = $subtitleIndex;
        // Burn selected subtitles into the transcode for predictable support across Chrome/Safari/Fire TV.
        $playbackRequest['AlwaysBurnInSubtitleWhenTranscoding'] = $subtitleIndex >= 0;
    }

    $info = jf_request('POST', "/Items/{$id}/PlaybackInfo", ['UserId' => $userId], $playbackRequest);
    $sources = (array)($info['MediaSources'] ?? []);
    if (!$sources) throw new RuntimeException('Jellyfin returned no playable media sources.');

    $source = [];
    foreach ($sources as $candidate) {
        if (is_array($candidate) && !empty($candidate['TranscodingUrl'])) {
            $source = $candidate;
            break;
        }
    }
    if (!$source) $source = is_array($sources[0] ?? null) ? $sources[0] : [];

    $mediaSourceId = (string)($source['Id'] ?? '');
    $playSessionId = (string)($info['PlaySessionId'] ?? '');
    if ($mediaSourceId === '') throw new RuntimeException('Jellyfin did not return a media source ID.');
    if ($playSessionId === '') $playSessionId = bin2hex(random_bytes(16));

    $transcodingUrl = trim((string)($source['TranscodingUrl'] ?? ''));
    if ($transcodingUrl === '') throw new RuntimeException('Jellyfin did not provide an HLS transcoding URL for this item.');

    $base = rtrim((string)$config['jellyfin_url'], '/');
    $streamUrl = $base . '/' . ltrim($transcodingUrl, '/');
    if (!preg_match('/(?:^|[?&])(?:api_key|ApiKey|X-Emby-Token)=/i', $streamUrl)) {
        $streamUrl .= (str_contains($streamUrl, '?') ? '&' : '?') . 'api_key=' . rawurlencode((string)$_SESSION['jf_token']);
    }

    $audioTracks = [];
    $subtitleTracks = [];
    foreach ((array)($source['MediaStreams'] ?? []) as $stream) {
        if (!is_array($stream) || !isset($stream['Index'])) continue;
        $streamType = (string)($stream['Type'] ?? '');
        $label = trim((string)($stream['DisplayTitle'] ?? $stream['Title'] ?? $stream['Language'] ?? ''));
        if ($label === '') $label = $streamType . ' ' . ((int)$stream['Index'] + 1);
        $track = [
            'index' => (int)$stream['Index'],
            'label' => $label,
            'language' => (string)($stream['Language'] ?? ''),
            'codec' => strtoupper((string)($stream['Codec'] ?? '')),
            'default' => (bool)($stream['IsDefault'] ?? false),
        ];
        if ($streamType === 'Audio') {
            $track['channels'] = (int)($stream['Channels'] ?? 0);
            $audioTracks[] = $track;
        } elseif ($streamType === 'Subtitle') {
            $track['forced'] = (bool)($stream['IsForced'] ?? false);
            $track['hearingImpaired'] = (bool)($stream['IsHearingImpaired'] ?? false);
            $subtitleTracks[] = $track;
        }
    }

    $selectedAudio = $audioIndex ?? ($source['DefaultAudioStreamIndex'] ?? null);
    $selectedSubtitle = $subtitleIndex ?? -1;

    json_response([
        'item' => $item,
        'streamUrl' => $streamUrl,
        'streamType' => 'hls',
        'mediaSourceId' => $mediaSourceId,
        'playSessionId' => $playSessionId,
        'startSeconds' => $positionOverride > 0 ? $positionOverride : (float)$item['positionSeconds'],
        'playMethod' => 'Transcode',
        'audioTracks' => $audioTracks,
        'subtitleTracks' => $subtitleTracks,
        'selectedAudio' => $selectedAudio !== null ? (int)$selectedAudio : null,
        'selectedSubtitle' => $selectedSubtitle !== null ? (int)$selectedSubtitle : -1,
        'selectedBitrate' => $maxBitrate,
        'qualityOptions' => [
            ['bitrate' => $serverMax, 'label' => 'Auto / Best'],
            ['bitrate' => min($serverMax, 8_000_000), 'label' => 'High'],
            ['bitrate' => min($serverMax, 5_000_000), 'label' => 'Medium'],
            ['bitrate' => min($serverMax, 2_500_000), 'label' => 'Data saver'],
        ],
        'diagnostics' => [
            'supportsTranscoding' => (bool)($source['SupportsTranscoding'] ?? true),
            'container' => (string)($source['Container'] ?? ''),
        ],
    ]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 502);
}

<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_auth_api();

$id = trim((string)($_GET['id'] ?? ''));
if ($id === '') json_response(['error' => 'Missing item id'], 422);

$userId = jf_user_id();
$isPs4 = sf_is_ps4_browser() || strtolower(trim((string)($_GET['platform'] ?? ''))) === 'ps4';
$requestedTransport = strtolower(trim((string)($_GET['transport'] ?? ($isPs4 ? 'mp4' : 'hls'))));
$ps4Transport = $isPs4 && $requestedTransport === 'hls' ? 'hls' : ($isPs4 ? 'mp4' : 'hls');
$configuredMax = (int)$config['max_streaming_bitrate'];
$ps4Max = (int)($config['ps4_max_streaming_bitrate'] ?? 4_000_000);
$ps4Medium = (int)($config['ps4_medium_streaming_bitrate'] ?? 2_500_000);
$ps4Data = (int)($config['ps4_data_streaming_bitrate'] ?? 1_200_000);
$ps4MaxWidth = (int)($config['ps4_max_width'] ?? 1280);
$ps4MaxHeight = (int)($config['ps4_max_height'] ?? 720);
$ps4MaxFramerate = (int)($config['ps4_max_framerate'] ?? 30);
$serverMax = $isPs4 ? min($configuredMax, $ps4Max) : $configuredMax;
$prefs = sf_preferences();
$requestedBitrate = max(0, (int)($_GET['bitrate'] ?? 0));
if ($requestedBitrate <= 0) $requestedBitrate = max(0, (int)($prefs['preferred_quality'] ?? 0));
$maxBitrate = $requestedBitrate > 0 ? min($serverMax, max(1_000_000, $requestedBitrate)) : $serverMax;
$audioIndex = isset($_GET['audio']) && $_GET['audio'] !== '' ? max(0, (int)$_GET['audio']) : null;
$subtitleIndex = isset($_GET['subtitle']) && $_GET['subtitle'] !== '' ? (int)$_GET['subtitle'] : null;
if ($subtitleIndex === null && ($prefs['default_subtitles'] ?? 'off') === 'off') $subtitleIndex = -1;
if ($subtitleIndex !== null && $subtitleIndex < -1) $subtitleIndex = -1;
$positionOverride = max(0.0, (float)($_GET['position'] ?? 0));

$deviceProfile = [
    'Name' => $isPs4 ? 'Schofestream PS4 Compatibility' : 'Schofestream Web HLS',
    'MaxStreamingBitrate' => $maxBitrate,
    'MaxStaticBitrate' => $maxBitrate,
    'MusicStreamingTranscodingBitrate' => 192000,
    'DirectPlayProfiles' => [],
    'TranscodingProfiles' => [[
        'Container' => ($isPs4 && $ps4Transport === 'mp4') ? 'mp4' : 'ts',
        'Type' => 'Video',
        'VideoCodec' => 'h264',
        'AudioCodec' => 'aac',
        'Protocol' => ($isPs4 && $ps4Transport === 'mp4') ? 'http' : 'hls',
        'Context' => ($isPs4 && $ps4Transport === 'mp4') ? 'Static' : 'Streaming',
        'EstimateContentLength' => ($isPs4 && $ps4Transport === 'mp4'),
        'EnableMpegtsM2TsMode' => false,
        'TranscodeSeekInfo' => 'Auto',
        'CopyTimestamps' => false,
        'EnableSubtitlesInManifest' => false,
        'EnableAudioVbrEncoding' => false,
        'MaxAudioChannels' => '2',
        'MinSegments' => $isPs4 ? ($ps4Transport === 'hls' ? 3 : 0) : 1,
        'SegmentLength' => $isPs4 ? ($ps4Transport === 'hls' ? 4 : 0) : 0,
        'BreakOnNonKeyFrames' => $isPs4 ? false : true,
    ]],
    'ContainerProfiles' => [],
    'CodecProfiles' => [[
        'Type' => 'Video',
        'Codec' => 'h264',
        'Conditions' => array_values(array_filter([
            ['Condition' => 'NotEquals', 'Property' => 'IsAnamorphic', 'Value' => 'true', 'IsRequired' => false],
            ['Condition' => 'EqualsAny', 'Property' => 'VideoProfile', 'Value' => $isPs4 ? 'main|baseline' : 'high|main|baseline|constrained baseline', 'IsRequired' => false],
            ['Condition' => 'LessThanEqual', 'Property' => 'VideoLevel', 'Value' => $isPs4 ? '40' : '51', 'IsRequired' => false],
            $isPs4 ? ['Condition' => 'LessThanEqual', 'Property' => 'VideoBitDepth', 'Value' => '8', 'IsRequired' => false] : null,
            $isPs4 ? ['Condition' => 'LessThanEqual', 'Property' => 'Width', 'Value' => (string)$ps4MaxWidth, 'IsRequired' => false] : null,
            $isPs4 ? ['Condition' => 'LessThanEqual', 'Property' => 'Height', 'Value' => (string)$ps4MaxHeight, 'IsRequired' => false] : null,
            $isPs4 ? ['Condition' => 'LessThanEqual', 'Property' => 'VideoFramerate', 'Value' => (string)$ps4MaxFramerate, 'IsRequired' => false] : null,
            $isPs4 ? ['Condition' => 'NotEquals', 'Property' => 'IsInterlaced', 'Value' => 'true', 'IsRequired' => false] : null,
        ])),
        'ApplyConditions' => [],
    ], [
        'Type' => 'Audio',
        'Codec' => 'aac',
        'Conditions' => $isPs4 ? [
            ['Condition' => 'LessThanEqual', 'Property' => 'AudioChannels', 'Value' => '2', 'IsRequired' => false],
            ['Condition' => 'LessThanEqual', 'Property' => 'AudioSampleRate', 'Value' => '48000', 'IsRequired' => false],
        ] : [],
        'ApplyConditions' => [],
    ]],
    'SubtitleProfiles' => $isPs4 ? [
        ['Format' => 'vtt', 'Method' => 'Encode'],
        ['Format' => 'srt', 'Method' => 'Encode'],
        ['Format' => 'ass', 'Method' => 'Encode'],
        ['Format' => 'ssa', 'Method' => 'Encode'],
        ['Format' => 'subrip', 'Method' => 'Encode'],
    ] : [
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
        'AllowVideoStreamCopy' => !$isPs4,
        'AllowAudioStreamCopy' => !$isPs4,
        'DeviceProfile' => $deviceProfile,
    ];
    if ($audioIndex !== null) $playbackRequest['AudioStreamIndex'] = $audioIndex;
    if ($subtitleIndex !== null) {
        $playbackRequest['SubtitleStreamIndex'] = $subtitleIndex;
        // Burn selected subtitles into the transcode for predictable support across Chrome/Safari/Fire TV.
        $playbackRequest['AlwaysBurnInSubtitleWhenTranscoding'] = $subtitleIndex >= 0;
    }

    $selectSource = static function(array $playbackInfo): array {
        $sources = (array)($playbackInfo['MediaSources'] ?? []);
        if (!$sources) throw new RuntimeException('Jellyfin returned no playable media sources.');
        foreach ($sources as $candidate) {
            if (is_array($candidate) && !empty($candidate['TranscodingUrl'])) return $candidate;
        }
        return is_array($sources[0] ?? null) ? $sources[0] : [];
    };

    $info = jf_request('POST', "/Items/{$id}/PlaybackInfo", ['UserId' => $userId], $playbackRequest);
    $source = $selectSource($info);

    // If the profile preference is "Jellyfin default", discover the server-selected subtitle
    // track first, then renegotiate once with that track burned into HLS for reliable browsers.
    if ($subtitleIndex === null && ($prefs['default_subtitles'] ?? 'off') === 'default') {
        $defaultSubtitle = $source['DefaultSubtitleStreamIndex'] ?? null;
        if ($defaultSubtitle !== null && (int)$defaultSubtitle >= 0) {
            $subtitleIndex = (int)$defaultSubtitle;
            $playbackRequest['SubtitleStreamIndex'] = $subtitleIndex;
            $playbackRequest['AlwaysBurnInSubtitleWhenTranscoding'] = true;
            $info = jf_request('POST', "/Items/{$id}/PlaybackInfo", ['UserId' => $userId], $playbackRequest);
            $source = $selectSource($info);
        }
    }

    $mediaSourceId = (string)($source['Id'] ?? '');
    $playSessionId = (string)($info['PlaySessionId'] ?? '');
    if ($mediaSourceId === '') throw new RuntimeException('Jellyfin did not return a media source ID.');
    if ($playSessionId === '') $playSessionId = bin2hex(random_bytes(16));

    $base = rtrim((string)$config['jellyfin_url'], '/');

    if ($isPs4) {
        // PS4 compatibility must not depend on MediaSourceInfo.TranscodingUrl. Jellyfin's
        // Video API accepts an explicit dynamic-transcode request using the media source id.
        // Progressive MP4 is preferred because the PS4 browser is more reliable with it;
        // native HLS remains available as an automatic fallback.
        $selectedAudioForUrl = $audioIndex ?? ($source['DefaultAudioStreamIndex'] ?? null);
        $query = [
            'static' => 'false',
            'MediaSourceId' => $mediaSourceId,
            'PlaySessionId' => $playSessionId,
            'VideoCodec' => 'h264',
            'AudioCodec' => 'aac',
            'VideoBitrate' => max(600000, $maxBitrate - 128000),
            'AudioBitrate' => 128000,
            'AudioSampleRate' => 48000,
            'MaxAudioChannels' => 2,
            'TranscodingMaxAudioChannels' => 2,
            'MaxFramerate' => $ps4MaxFramerate,
            'MaxWidth' => $ps4MaxWidth,
            'MaxHeight' => $ps4MaxHeight,
            'MaxVideoBitDepth' => 8,
            'Profile' => 'main',
            'Level' => '4.0',
            'MaxRefFrames' => 4,
            'RequireAvc' => 'true',
            'RequireNonAnamorphic' => 'true',
            'DeInterlace' => 'true',
            'AllowVideoStreamCopy' => 'false',
            'AllowAudioStreamCopy' => 'false',
            'EnableAutoStreamCopy' => 'false',
            'EnableAudioVbrEncoding' => 'false',
            'Context' => 'Streaming',
            'api_key' => (string)$_SESSION['jf_token'],
        ];
        if ($selectedAudioForUrl !== null) $query['AudioStreamIndex'] = (int)$selectedAudioForUrl;
        if ($subtitleIndex !== null && $subtitleIndex >= 0) {
            $query['SubtitleStreamIndex'] = $subtitleIndex;
            $query['SubtitleMethod'] = 'Encode';
        }
        if ($positionOverride > 0) $query['StartTimeTicks'] = (int)round($positionOverride * 10000000);

        if ($ps4Transport === 'hls') {
            $query['SegmentContainer'] = 'ts';
            $query['MinSegments'] = 1;
            $query['SegmentLength'] = 3;
            $query['BreakOnNonKeyFrames'] = 'false';
            $streamPath = '/Videos/' . rawurlencode($id) . '/master.m3u8';
        } else {
            $streamPath = '/Videos/' . rawurlencode($id) . '/stream.mp4';
        }
        $streamUrl = $base . $streamPath . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    } else {
        $transcodingUrl = trim((string)($source['TranscodingUrl'] ?? ''));
        if ($transcodingUrl === '') throw new RuntimeException('Jellyfin did not provide a transcoding URL for this item.');
        $streamUrl = $base . '/' . ltrim($transcodingUrl, '/');
        if (!preg_match('/(?:^|[?&])(?:api_key|ApiKey|X-Emby-Token)=/i', $streamUrl)) {
            $streamUrl .= (str_contains($streamUrl, '?') ? '&' : '?') . 'api_key=' . rawurlencode((string)$_SESSION['jf_token']);
        }
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
    $selectedSubtitle = $subtitleIndex ?? ($source['DefaultSubtitleStreamIndex'] ?? -1);

    json_response([
        'item' => $item,
        'streamUrl' => $streamUrl,
        'streamType' => $isPs4 && $ps4Transport === 'mp4' ? 'mp4' : 'hls',
        'mediaSourceId' => $mediaSourceId,
        'playSessionId' => $playSessionId,
        'startSeconds' => $positionOverride > 0 ? $positionOverride : (float)$item['positionSeconds'],
        'playMethod' => 'Transcode',
        'audioTracks' => $audioTracks,
        'subtitleTracks' => $subtitleTracks,
        'selectedAudio' => $selectedAudio !== null ? (int)$selectedAudio : null,
        'selectedSubtitle' => $selectedSubtitle !== null ? (int)$selectedSubtitle : -1,
        'selectedBitrate' => $maxBitrate,
        'preferences' => ['autoplayNext' => (bool)($prefs['autoplay_next'] ?? true), 'defaultSubtitles' => (string)($prefs['default_subtitles'] ?? 'off')],
        'platform' => $isPs4 ? 'ps4' : 'web',
        'nativeHls' => $isPs4 && $ps4Transport === 'hls',
        'transport' => $isPs4 ? $ps4Transport : 'hls',
        'qualityOptions' => $isPs4 ? [
            ['bitrate' => min($serverMax, $ps4Max), 'label' => 'PS4 720p'],
            ['bitrate' => min($serverMax, $ps4Medium), 'label' => 'PS4 Medium'],
            ['bitrate' => min($serverMax, $ps4Data), 'label' => 'PS4 Data saver'],
        ] : [
            ['bitrate' => $serverMax, 'label' => 'Auto / Best'],
            ['bitrate' => min($serverMax, 8_000_000), 'label' => 'High'],
            ['bitrate' => min($serverMax, 5_000_000), 'label' => 'Medium'],
            ['bitrate' => min($serverMax, 2_500_000), 'label' => 'Data saver'],
        ],
        'diagnostics' => [
            'supportsTranscoding' => (bool)($source['SupportsTranscoding'] ?? true),
            'container' => (string)($source['Container'] ?? ''),
            'platform' => $isPs4 ? 'PlayStation 4' : 'Web',
            'maxBitrate' => $serverMax,
            'h264Level' => $isPs4 ? '4.0' : '5.1',
            'maxResolution' => $isPs4 ? $ps4MaxWidth . 'x' . $ps4MaxHeight : 'profile default',
            'maxFramerate' => $isPs4 ? $ps4MaxFramerate : null,
            'forcedTranscode' => $isPs4,
            'transport' => $isPs4 ? $ps4Transport : 'hls',
        ],
    ]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 502);
}

<?php
declare(strict_types=1);

return [
    'app_name' => 'Schofestream',
    'app_version' => '0.3.1',
    'jellyfin_url' => 'https://player.schofestream.co.uk',
    'client_name' => 'Schofestream Web',
    'client_version' => '0.3.1',
    'verify_ssl' => true,
    'request_timeout' => 20,
    'max_streaming_bitrate' => 12_000_000,
    'session_name' => 'schofestream_session',
    'home_row_limit' => 18,
    'instant_search_limit' => 8,
    'metadata_cache_ttl' => 20,
    'image_quality' => 84,
    'settings_path' => dirname(__DIR__) . '/schofestream-data/settings',
    'history_limit' => 80,
];

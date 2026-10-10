<?php
declare(strict_types=1);

return [
    'app_name' => 'Schofestream',
    'app_version' => '0.3.6',
    'jellyfin_url' => 'https://player.schofestream.co.uk',
    'client_name' => 'Schofestream Web',
    'client_version' => '0.3.6',
    'verify_ssl' => true,
    'request_timeout' => 20,
    'max_streaming_bitrate' => 12_000_000,
    'ps4_max_streaming_bitrate' => 4_000_000,
    'ps4_medium_streaming_bitrate' => 2_500_000,
    'ps4_data_streaming_bitrate' => 1_200_000,
    'ps4_max_width' => 1280,
    'ps4_max_height' => 720,
    'ps4_max_framerate' => 30,
    'session_name' => 'schofestream_session',
    'home_row_limit' => 18,
    'instant_search_limit' => 8,
    'metadata_cache_ttl' => 20,
    'image_quality' => 84,
    'settings_path' => dirname(__DIR__) . '/schofestream-data/settings',
    'households_path' => dirname(__DIR__) . '/schofestream-data/households',
    'profile_key_path' => dirname(__DIR__) . '/schofestream-data/profile.key',
    'max_profiles' => 5,
    'history_limit' => 80,
];

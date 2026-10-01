<?php
declare(strict_types=1);

function sf_default_preferences(): array
{
    return [
        'preferred_quality' => 0,
        'autoplay_next' => true,
        'default_subtitles' => 'off',
        'home_rows' => [
            'continueWatching', 'nextUp', 'becauseYouWatched', 'recentlyWatched',
            'favorites', 'recentMovies', 'recentShows', 'genres', 'collections'
        ],
        'hidden_home_rows' => [],
    ];
}

function sf_settings_file(): string
{
    global $config;
    $base = rtrim((string)($config['settings_path'] ?? (__DIR__ . '/../storage/settings')), '/');
    if (!is_dir($base) && !@mkdir($base, 0750, true) && !is_dir($base)) {
        throw new RuntimeException('Schofestream settings storage is not writable.');
    }
    $userId = jf_user_id();
    if ($userId === '') {
        throw new RuntimeException('No authenticated user.');
    }
    return $base . '/' . hash('sha256', $userId) . '.json';
}

function sf_preferences(): array
{
    $defaults = sf_default_preferences();
    try {
        $file = sf_settings_file();
        if (!is_file($file)) {
            return $defaults;
        }
        $raw = @file_get_contents($file);
        $saved = is_string($raw) ? json_decode($raw, true) : null;
        return is_array($saved) ? sf_sanitize_preferences(array_replace($defaults, $saved)) : $defaults;
    } catch (Throwable) {
        return $defaults;
    }
}

function sf_sanitize_preferences(array $input): array
{
    $defaults = sf_default_preferences();
    $allowedRows = $defaults['home_rows'];
    $qualityOptions = [0, 2_500_000, 5_000_000, 8_000_000, 12_000_000];

    $quality = (int)($input['preferred_quality'] ?? 0);
    if (!in_array($quality, $qualityOptions, true)) {
        $quality = 0;
    }

    $subtitle = (string)($input['default_subtitles'] ?? 'off');
    if (!in_array($subtitle, ['off', 'default'], true)) {
        $subtitle = 'off';
    }

    $rows = array_values(array_unique(array_filter(
        array_map('strval', (array)($input['home_rows'] ?? [])),
        static fn(string $row): bool => in_array($row, $allowedRows, true)
    )));
    foreach ($allowedRows as $row) {
        if (!in_array($row, $rows, true)) {
            $rows[] = $row;
        }
    }

    $hidden = array_values(array_unique(array_filter(
        array_map('strval', (array)($input['hidden_home_rows'] ?? [])),
        static fn(string $row): bool => in_array($row, $allowedRows, true)
    )));

    // 0.3.0 compatibility: the old standalone visibility toggles duplicated row visibility.
    if (array_key_exists('show_recently_watched', $input) && !$input['show_recently_watched']) {
        $hidden[] = 'recentlyWatched';
    }
    if (array_key_exists('show_recommendations', $input) && !$input['show_recommendations']) {
        $hidden[] = 'becauseYouWatched';
    }
    $hidden = array_values(array_unique($hidden));

    return [
        'preferred_quality' => $quality,
        'autoplay_next' => (bool)($input['autoplay_next'] ?? true),
        'default_subtitles' => $subtitle,
        'home_rows' => $rows,
        'hidden_home_rows' => $hidden,
    ];
}

function sf_home_row_visible(array $preferences, string $row): bool
{
    return !in_array($row, (array)($preferences['hidden_home_rows'] ?? []), true);
}

function sf_save_preferences(array $input): array
{
    $prefs = sf_sanitize_preferences(array_replace(sf_preferences(), $input));
    $file = sf_settings_file();
    $tmp = $file . '.' . bin2hex(random_bytes(6)) . '.tmp';
    $payload = json_encode($prefs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($payload === false || @file_put_contents($tmp, $payload, LOCK_EX) === false) {
        @unlink($tmp);
        throw new RuntimeException('Could not save Schofestream preferences. Check storage permissions.');
    }
    @chmod($tmp, 0640);
    if (!@rename($tmp, $file)) {
        @unlink($tmp);
        throw new RuntimeException('Could not finish saving Schofestream preferences.');
    }
    return $prefs;
}

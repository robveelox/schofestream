# Schofestream 0.3.0

Schofestream is a custom PHP/JavaScript frontend for Jellyfin. The website runs on ordinary DirectAdmin/shared hosting while Jellyfin remains responsible for authentication, media metadata, playback state, transcoding and delivery of the actual video stream.

## What 0.3.0 adds

0.3.0 is the Personalisation release. It adds:

- Profile/account screen using the signed-in Jellyfin identity
- Jellyfin avatar display with a safe fallback
- Watch History with Movies / TV Episode filters
- Recently Watched on Home
- “Because You Watched…” recommendations using Jellyfin Similar items
- Viewing-history-derived genre rows
- Continue Watching removal without marking the item watched
- Persistent Schofestream playback preferences
- Preferred streaming quality
- Autoplay-next-episode preference
- Default subtitle behaviour
- Home row reordering
- Home row show/hide controls
- Current Schofestream session/device information
- A settings-storage check in Diagnostics

Authentication remains Jellyfin-only. Schofestream does not store passwords or create a second account system.

## Updating from 0.2.2

Upload the contents of the 0.3.0 update-only package over the existing 0.2.2 installation, preserving folders. New files must also be uploaded.

After uploading, hard-refresh the browser so static assets load as `?v=0.3.0`.

## Requirements

- PHP 8.1+
- PHP cURL, JSON and session support
- HTTPS
- A reachable Jellyfin server
- A writable directory for Schofestream profile preferences

## Preference storage

Schofestream-specific preferences are stored outside the public web root by default:

```text
../schofestream-data/settings/
```

The folder is created automatically when possible. It contains one hashed JSON filename per Jellyfin user and stores preferences only. It never stores Jellyfin passwords or access tokens.

If DirectAdmin prevents PHP from creating/writing the default location, set `settings_path` in `config.php` to another non-public writable directory owned by the PHP user.

Run `/diagnostics.php` after updating and confirm **Preferences storage = OK**.

## Jellyfin data vs Schofestream data

Jellyfin remains the source of truth for:

- users and passwords
- favourites / My List
- watched state
- resume positions
- watch history dates
- media metadata
- libraries and artwork
- playback/transcoding

Schofestream stores only frontend preferences such as quality, autoplay and Home layout.

## Playback

Video still travels directly from `player.schofestream.co.uk` to the viewer. DirectAdmin does not proxy video bytes.

The 0.3.0 player keeps the stable H.264/AAC HLS path from 0.2.2. Changing audio, subtitle or quality renegotiates a Jellyfin HLS session while keeping the current timestamp.

If **Jellyfin default** subtitles are enabled in Schofestream settings, playback first discovers Jellyfin's default subtitle track and, when one exists, requests it in the HLS transcode for consistent browser display.

## Main pages

```text
/                     Personalised Home
/account.php           Profile/account
/settings.php          Schofestream preferences
/history.php           Watch History
/my-list.php           Jellyfin favourites
/library.php            Movies / TV library
/details.php            Movie / series / collection details
/watch.php              Custom HLS player
/diagnostics.php        Installation/connection checks
```

## Important API additions

```text
/api/profile.php
/api/settings.php
/api/history.php
/api/remove-resume.php
/api/avatar.php
```

## Security

Jellyfin access tokens remain in the server-side PHP session. They are not stored in Schofestream preference files or browser localStorage.

The HLS URL itself still requires the signed-in user's Jellyfin token so the browser can fetch video segments directly. This is the user's token, not an administrator API key. Treat reverse-proxy access logs as sensitive because stream URLs may contain authentication values.

## Recommended post-update test

1. Sign in and open `/diagnostics.php`.
2. Confirm Preferences storage and Jellyfin connection both show OK.
3. Open Settings, change quality/autoplay/Home ordering, save, sign out and sign back in, and confirm settings persisted.
4. Start a movie, return Home, then remove it from Continue Watching.
5. Open Watch History and test both filters.
6. Play an episode to the end with autoplay enabled and disabled.
7. Test subtitle default behaviour on a title that actually has subtitle tracks.

# Schofestream 0.3.6

Schofestream is a lightweight PHP/JavaScript streaming frontend backed by Jellyfin. Jellyfin remains responsible for media libraries, metadata, playback/transcoding, per-user watch state and favourites; Schofestream provides the branded web interface, household profiles, personalisation and browser-specific playback handling.

## Current release

**Version 0.3.6** includes:

- Jellyfin username/password authentication with server-side PHP sessions
- Movies, TV shows, seasons and episodes
- Continue Watching, Next Up, Recently Watched and Recently Added rows
- Search, genres, collections, people/cast and related titles
- My List using Jellyfin favourites
- Watch history and watched/unwatched controls
- Custom player with progress reporting, audio/subtitle selection and quality controls
- Autoplay/Up Next for episodes
- Per-user Schofestream preferences
- Netflix-style **Who's Watching?** household profiles mapped to separate Jellyfin users
- Optional profile PINs
- PS4 browser compatibility mode with a conservative Jellyfin transcode path

## Requirements

- PHP 8.1+
- PHP cURL, JSON and OpenSSL extensions
- HTTPS
- A reachable Jellyfin server
- Writable private storage outside the public web root

The default Jellyfin endpoint is configured in `config.php` as:

```text
https://player.schofestream.co.uk
```

Change it there if your Jellyfin server uses another address.

## Installation

1. Copy the repository contents into the web root for Schofestream, for example `public_html`.
2. Confirm `config.php` points at the correct Jellyfin URL.
3. Make sure PHP can create/write the private data directory one level above the web root.
4. Open Schofestream in the browser and sign in with the Jellyfin account that should own the household.
5. Open `/diagnostics.php` after signing in and confirm the environment checks pass.

By default Schofestream stores private runtime data at:

```text
../schofestream-data/settings/
../schofestream-data/households/
../schofestream-data/profile.key
```

These files are runtime/private data and must **not** be committed to GitHub.

## Who's Watching profiles

Schofestream profiles are not cosmetic aliases over one Jellyfin user. Each viewing profile maps to a separate Jellyfin user so watch progress, Continue Watching, My List, Next Up and history remain independent.

Typical setup:

```text
Schofestream household
├── Rob   -> Jellyfin user Rob
├── Guest -> Jellyfin user Guest
└── Kids  -> Jellyfin user Kids
```

To add a watcher:

1. Create the additional user in **Jellyfin Dashboard -> Users**.
2. In Schofestream open **Manage Profiles**.
3. Choose **Add Profile**.
4. Enter the Schofestream profile name/avatar and optional 4-digit PIN.
5. Enter the linked Jellyfin user's username/password once.
6. Schofestream authenticates that account, discards the password and stores the returned access token encrypted outside the web root.

Removing a Schofestream profile does not delete the Jellyfin user.

## Preferences

Schofestream-specific settings are stored per underlying Jellyfin user, including options such as preferred quality, subtitles, autoplay and Home row layout. Jellyfin remains the source of truth for watched state, favourites and playback positions.

## Playback

Modern browsers use the normal Schofestream/Jellyfin HLS path with hls.js where needed.

### PlayStation 4

The PS4 browser uses a dedicated compatibility path. Schofestream asks Jellyfin to fully transcode to a conservative H.264/AAC format and prefers progressive MP4, with native HLS as a fallback.

Current PS4 constraints are configured in `config.php`:

```text
1280x720 maximum
30 fps maximum
H.264 Main Level 4.0
AAC stereo / 48 kHz
4 Mbps normal ceiling
2.5 Mbps medium fallback
1.2 Mbps data-saver fallback
```

The player automatically tries lower PS4-compatible transports/bitrates if the first stream cannot remain open.

## Security

- Jellyfin passwords are never persisted by Schofestream.
- Linked-profile Jellyfin access tokens are encrypted at rest with AES-256-GCM.
- The profile encryption key is generated outside the public web root.
- Authentication/session tokens are never hard-coded into the repository.
- Do not add Jellyfin administrator API keys, user passwords, household JSON files or `profile.key` to source control.
- `config.php` contains service configuration only and should remain free of credentials.

## GitHub/source-control notes

The included `.gitignore` excludes common local/editor files plus Schofestream runtime storage, `.env` files, logs, temporary files and key files. Before publishing a fork, still inspect your working tree for any locally added credentials or exported runtime data.

## Diagnostics

After signing in and selecting a profile, open:

```text
/diagnostics.php
```

The diagnostics page checks the PHP environment, Jellyfin connectivity, private preference/household storage and profile encryption-key handling. On PS4 it also reports the detected compatibility mode.

## Project structure

```text
api/                 Schofestream JSON endpoints
assets/css/          Application styling
assets/js/           Browser/client modules
includes/            Bootstrap, Jellyfin client, preferences and household helpers
config.php           Application/Jellyfin configuration
index.php            Home experience
watch.php            Video player
profiles.php         Who's Watching screen
manage-profiles.php  Household profile management
diagnostics.php      Deployment/runtime checks
```

## Release history

See `CHANGELOG.md` for version-by-version changes.

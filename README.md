# Schofestream 0.3.1

Schofestream is a custom PHP/JavaScript frontend for a Jellyfin server. Jellyfin remains responsible for authentication, media metadata, playback state, transcoding and streaming; Schofestream provides the branded web experience.

## Requirements

- PHP 8.1+
- cURL and JSON PHP extensions
- HTTPS
- A reachable Jellyfin server
- A writable Schofestream settings directory (configured in `config.php`, outside the web root by default)

## Upgrade from 0.3.0

Overwrite the changed files from the 0.3.1 update package, then remove these legacy files if they still exist from older installs:

- `api/session.php`
- `CADDY-CORS-FIX.txt`
- `CONTINUE-WATCHING-FIX.txt`
- `INSTALL-FIRST.txt`
- `PLAYBACK-CSP-FIX.txt`
- `PLAYER-OVERLAY-FIX.txt`

Existing 0.3.0 preference JSON files remain compatible. The first read automatically translates the old duplicated Home visibility flags into the current row visibility model.

## Configuration

Edit `config.php` if your Jellyfin URL or deployment paths differ. Do not place administrator API keys in browser JavaScript or public source files.

## Diagnostics

After deployment, sign in and open `/diagnostics.php` to verify PHP extensions, settings storage and Jellyfin connectivity.

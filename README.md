# Schofestream 0.1.1

A custom PHP/JavaScript streaming frontend for Jellyfin, designed to run on ordinary DirectAdmin shared hosting while the Jellyfin server continues to handle media storage, transcoding and video delivery.


## Playback requirement for 0.1.1

Schofestream and Jellyfin are on different HTTPS origins. Chromium/Firefox playback uses hls.js, which fetches the HLS manifest and segments with JavaScript. Your Jellyfin reverse proxy therefore needs to allow `https://schofestream.co.uk` with CORS response headers.

Use the exact Caddy block in `CADDY-CORS-FIX.txt`, validate Caddy, and reload it before testing playback. The media still travels directly from `player.schofestream.co.uk` to the viewer; the DirectAdmin hosting does **not** proxy video bytes.

The 0.1.1 player intentionally requests H.264/AAC HLS transcoding for compatibility. Direct-play optimization is a later step after reliable playback is confirmed.

## Included in 0.1

- Jellyfin username/password sign-in
- Server-side PHP sessions (the Jellyfin access token is not stored in localStorage)
- Streaming-style responsive home page
- Continue Watching
- Recently Added
- Movie library
- TV library
- Search
- Movie details
- Series / season / episode browser
- HLS video playback through Jellyfin
- Resume playback from the user's Jellyfin watch position
- Playback start/progress/stop reporting to Jellyfin
- Responsive mobile/desktop layout
- Artwork proxying through PHP so artwork requests do not expose the user token
- Basic CSP/security headers and CSRF protection
- Diagnostics page

## Requirements

- PHP 8.1 or newer
- PHP cURL extension
- PHP JSON/session support
- HTTPS strongly recommended
- A reachable Jellyfin server

No database is required by Schofestream 0.1. User accounts and media data remain in Jellyfin.

## Deployment on DirectAdmin

1. In DirectAdmin, create/confirm the domain `schofestream.co.uk` and enable a valid Let's Encrypt SSL certificate.
2. Open the domain's `public_html` directory.
3. Upload the **contents** of this package into `public_html` (not the parent folder itself unless you deliberately want a subdirectory).
4. Open `config.php` and confirm:

```php
'jellyfin_url' => 'https://player.schofestream.co.uk',
```

5. Confirm PHP 8.1+ and the cURL extension are enabled for the domain.
6. Visit `https://schofestream.co.uk/login.php` and sign in with an existing Jellyfin user.
7. After signing in, visit `https://schofestream.co.uk/diagnostics.php`. All four PHP checks and the Jellyfin connection should show **OK**.

## SSL

Do not run the finished service over plain HTTP. Both the frontend and Jellyfin endpoint should use HTTPS:

- `https://schofestream.co.uk`
- `https://player.schofestream.co.uk`

The included `.htaccess` contains an optional HTTPS redirect. It is commented out so you can obtain/fix the certificate first. Once HTTPS is working, uncomment these lines:

```apache
RewriteCond %{HTTPS} !=on
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

## Jellyfin / browser playback

Schofestream's PHP backend handles login and normal API requests server-side. Video is intentionally **not** proxied through your shared hosting account. The browser receives an HLS stream URL and downloads video segments directly from `player.schofestream.co.uk`.

This keeps high-bandwidth video traffic off DirectAdmin/shared hosting.

Jellyfin's CORS configuration defaults are normally sufficient for this client. If the player loads the site but the browser console reports CORS errors while loading `.m3u8` or segment requests, configure the Jellyfin server's allowed CORS hosts to include:

```text
https://schofestream.co.uk
```

Do not put an administrator API key into the JavaScript.

## HLS.js

For browsers that do not have reliable native HLS support, 0.1 loads a pinned HLS.js 1.7.3 build from jsDelivr. Safari can use native HLS. If you later want a completely self-contained package, download `hls.min.js` 1.7.3 into `assets/vendor/` and change the script tag in `watch.php` to point to the local file.

## Security model

On successful login, Jellyfin returns a token for that user. Schofestream stores it only in the server-side PHP session. Normal library/API calls happen PHP -> Jellyfin, so the token is not exposed in frontend JavaScript.

The exception is the actual Jellyfin HLS stream URL. Jellyfin's browser player requires the user token to authorize direct segment delivery, so the user's own token is included in the generated playback URL. This is **not an administrator API key**. Treat Jellyfin/reverse-proxy access logs as sensitive because URLs can contain authentication values.

## File layout

```text
/
├── index.php                 Home
├── login.php                 Login screen
├── logout.php                Ends Schofestream PHP session
├── library.php               Movies / TV library
├── search.php                Search results
├── details.php               Movie / series details
├── watch.php                 Video player
├── diagnostics.php           Installation checks
├── config.php                Main configuration
├── api/
│   ├── login.php
│   ├── session.php
│   ├── home.php
│   ├── library.php
│   ├── search.php
│   ├── item.php
│   ├── episodes.php
│   ├── image.php
│   ├── playback.php
│   └── playback-event.php
├── includes/
│   ├── bootstrap.php
│   ├── jellyfin.php
│   └── layout.php
└── assets/
    ├── css/app.css
    └── js/
```

## Configuration

`config.php` contains the settings you are most likely to edit:

```php
'jellyfin_url' => 'https://player.schofestream.co.uk',
'max_streaming_bitrate' => 12_000_000,
'verify_ssl' => true,
```

`max_streaming_bitrate` is currently 12 Mbps. Lower it if remote streaming is too demanding on the Jellyfin server/uplink.

Do not set `verify_ssl` to `false` in production.

## Playback behaviour in 0.1

Version 0.1 prioritises compatibility: it requests an H.264/AAC HLS stream from Jellyfin. That means Jellyfin may transcode media even when the original file could theoretically direct-play in the browser.

A later release should add device profiling/direct-play selection so supported MP4/H.264/AAC media avoids unnecessary transcoding.

## Troubleshooting

### Login says Jellyfin connection failed

- Check `config.php`.
- Make sure DirectAdmin's PHP can make outgoing HTTPS connections.
- Verify the PHP cURL extension is enabled.
- Open `https://player.schofestream.co.uk` separately and confirm Jellyfin is online.

### Login works but video does not play

- Open browser developer tools and inspect Network/Console.
- Check Jellyfin FFmpeg/transcoding logs.
- Confirm the Jellyfin user has permission to play the media and access the server remotely.
- Look for CORS errors from `player.schofestream.co.uk`.
- Try lowering `max_streaming_bitrate` in `config.php`.

### Artwork is missing

Schofestream proxies artwork through `/api/image.php`. Check the browser Network panel for a 404/500 from that endpoint and then check whether that title actually has the requested artwork in Jellyfin.

## Version scope

0.1 is the first functional streaming build. It intentionally does not yet include:

- My List / favourites UI
- Multiple Schofestream profiles under a single account
- Subtitle/audio track selector
- Next-episode autoplay
- Full custom video controls
- Direct-play optimisation
- PWA/offline shell
- Admin portal
- Seerr request UI

Those are suitable follow-up versions once the basic Jellyfin login -> browse -> play -> resume loop is confirmed on your real server.

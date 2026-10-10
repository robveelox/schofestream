# 0.3.6

- Fix PS4 playback when Jellyfin omits `TranscodingUrl` from `PlaybackInfo`.
- Build PS4 transcode URLs directly through Jellyfin's Video API using `MediaSourceId`.
- Prefer progressive H.264/AAC MP4 on PS4; retain native HLS as automatic fallback.
- Keep forced H.264 Main 4.0, AAC stereo, 720p/30fps compatibility constraints.
- Preserve automatic bitrate fallback.

### 0.3.5 — PS4 playback reliability

- PS4 playback now forces video and audio transcoding rather than permitting stream-copy.
- Reduced PS4 output to a conservative 1280×720, 30 fps, H.264 Main/Baseline Level 4.0 and AAC stereo profile.
- Reduced the normal PS4 ceiling from 8 Mbps to 4 Mbps.
- Added automatic HLS fallback steps at 4 Mbps, 2.5 Mbps and 1.2 Mbps.
- Added progressive H.264/AAC MP4 fallbacks if native HLS remains unstable.
- Added 10-second PS4 stall detection with automatic stream renegotiation.
- PS4 media now uses eager preload while other browsers keep metadata preload.
- Updated PS4 diagnostics and bumped Schofestream to 0.3.5.

## 0.3.4 — PlayStation 4 browser compatibility

- Added automatic PlayStation 4 browser detection across Schofestream.
- Added a dedicated PS4 Jellyfin playback profile using H.264 8-bit, H.264 Level 4.1, AAC stereo, native HLS, a 1920×1080 ceiling and an 8 Mbps maximum streaming bitrate.
- PS4 playback bypasses hls.js/Media Source Extensions and attaches Jellyfin HLS directly to the HTML5 video element.
- Added an automatic 5 Mbps retry when the PS4 rejects the initial compatibility stream.
- Selected subtitles continue to be burned into transcoded video for predictable PS4 playback.
- hls.js is no longer loaded at all on PS4 pages, reducing memory usage.
- Added PS4 controller/D-pad focus navigation across non-player pages.
- Added strong TV-distance focus states and lighter PS4 rendering with backdrop-filter and heavy transitions disabled.
- Added PS4 platform/playback-profile information to Diagnostics.
- Added `ps4_max_streaming_bitrate` configuration, defaulting to 8 Mbps.
- Bumped Schofestream web/client version to 0.3.4.

## 0.3.3 — Who's Watching profiles

- Added a Netflix-style **Who's Watching?** screen after household sign-in.
- Added Schofestream households with one owner login and up to five viewing profiles by default.
- Each profile maps to a separate Jellyfin user so Continue Watching, watched status, favourites, Next Up and history remain independent.
- Added **Manage Profiles** with add, edit, relink and remove flows.
- Added eight clean Schofestream profile avatar themes with no emoji artwork.
- Added optional 4-digit profile PINs stored only as password hashes.
- Linked Jellyfin passwords are used only during setup and are never stored.
- Linked Jellyfin access tokens are encrypted at rest with AES-256-GCM using a private key outside the web root.
- Added profile switching without re-entering linked-user passwords.
- Added automatic first-run household creation that turns the existing login into the owner profile.
- Added account-page controls for Switch Profile and Manage Profiles.
- Updated the authenticated header to display the active Schofestream profile rather than only the underlying Jellyfin username.
- Added household/profile storage and encryption diagnostics.
- Bumped Schofestream web/client version to 0.3.3.

## 0.3.2 — UI icon cleanup

- Replaced decorative emoji/glyph controls with a consistent inline SVG icon system.
- Removed emoji-style play, volume, settings, logout, navigation, watched and reorder symbols.
- Replaced star-rating decoration with a plain `Rating` label.
- Simplified My List / watched button copy and removed arrow decorations from CTAs where unnecessary.
- Preserved accessible labels for icon-only controls.

# Schofestream changelog

## 0.3.1 — Cleanup & mobile sign-out

- Added an explicit mobile sign-out control to the authenticated header.
- Sign-out now best-effort reports the Jellyfin session as ended before clearing the local PHP session.
- Removed the unused `/api/session.php` endpoint from the application tree.
- Removed obsolete 0.1.x upgrade/fix-note files from the maintained source tree.
- Simplified Home visibility preferences: row visibility is now controlled only by `hidden_home_rows` instead of duplicated standalone booleans.
- Added compatibility migration for existing 0.3.0 preference files so hidden Recently Watched / Because You Watched choices are preserved.
- Removed duplicated Home-screen visibility controls from Settings.
- Removed stale/dead CSS selectors and legacy version-specific patch comments.
- Account version display is now supplied by the server rather than hard-coded in JavaScript.
- Consolidated mobile navigation styling and stale responsive rules.
- Bumped application/client assets to 0.3.1.

## 0.3.0 — Personalisation

- Added profile/account and watch-history experiences.
- Added per-user Schofestream playback and Home preferences.
- Added Recently Watched and Because You Watched personalisation.
- Added Continue Watching management.

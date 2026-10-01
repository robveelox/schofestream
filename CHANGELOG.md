# Changelog

## 0.2.2 — 1 October 2026
- Hardened movie, series, episode, collection and person/detail rendering for missing artwork and metadata.
- Details pages now tolerate optional Jellyfin endpoint failures, clamp long descriptions, and safely expand large cast lists.
- Added selectable audio tracks, subtitles and playback quality. Changes renegotiate Jellyfin HLS at the current timestamp.
- Added buffering state, stronger HLS retry/recovery, improved next-episode handling and iPhone/Safari fullscreen fallback.
- Polished the home screen with skeleton loading, safer hero selection, partial-row failure handling and reordered high-value rows.
- Split global instant search out of app.js; added request cancellation, faster debounce, keyboard navigation and stronger empty/error states.
- My List now supports sorting and instant removal without a page reload.
- Added mobile safe-area handling, landscape-player tweaks and larger touch targets.
- Reduced artwork request sizes and added a small per-user metadata cache for shared-hosting performance.
- Added consistent image placeholders, friendly retry states, focus-visible styling, a skip link and reduced-motion support.
- Playback stop events now invalidate metadata cache so Continue Watching refreshes promptly after playback.
- Bumped all static asset/client identifiers to 0.2.2.

## 0.1.5 — 1 October 2026
- Changed Continue Watching to use Schofestream's own unfinished-playback query.
- Shows up to 20 recently played Movies/Episodes with a saved playback position greater than zero.
- No longer relies on Jellyfin's Resume endpoint thresholds, so briefly started titles can still appear.
- Keeps Continue Watching ordered by most recently played.

## 0.1.4 — 1 October 2026
- Fixed the black player overlay remaining above a successfully playing video.
- Added an explicit `.player-overlay[hidden] { display: none !important; }` rule so loading/error overlays actually disappear when JavaScript hides them.
- Bumped frontend cache/version identifiers to 0.1.4.

# Schofestream changelog

## 0.1.3 — 1 October 2026

- Fixed hls.js playback being blocked by Content Security Policy.
- Added `worker-src 'self' blob:` so the hls.js transmuxing worker can start.
- Added `data:` to `media-src` for the in-memory WebVTT subtitle track used by the player.
- Bumped frontend cache/version identifiers to 0.1.3.
- Added a local SVG favicon to remove the unrelated `/favicon.ico` 404 console noise.

# Schofestream Changelog

## 0.1.2
- Corrected the Jellyfin/Caddy CORS instructions.
- Removed the incorrect recommendation to add a second `Access-Control-Allow-Origin` response header in Caddy.
- Jellyfin's existing CORS response is now left intact.
- No application playback-code changes from 0.1.1 are required for this specific CORS failure.

## 0.1.1
- Fixed Jellyfin `PlaybackInfo` device profile placement.
- Added browser-compatible H.264/AAC HLS playback profile.
- Improved playback diagnostics and retry handling.

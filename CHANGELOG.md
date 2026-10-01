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

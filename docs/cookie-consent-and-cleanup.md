# Cookie visibility and shared control fixes

Release 0.3.7 restores a compact fixed cookie-preference banner on every page.
The previous page-flow override placed it after the footer, beyond the first
viewport. It now respects safe areas and short landscape viewports, with 44px
actions and scrolling for unusually long configured copy.

The banner's measured height reserves space below the footer and scroll targets.
Dismissal clears that space and only records a session dismissal; it never
accepts preferences or dispatches the consent event. Acceptance retains the
existing persistent preference and consent event. Tests exercise acceptance
only in the isolated preview.

Back-to-top uses the actual button height and sticky header boundary. It stays
above the banner and footer where space exists, and hides when a short viewport
has no unobstructed position. Search and mobile dialogs retain keyboard focus
in Safari and Chromium, and closing returns focus to their opening button.

Changed implementation: `assets/css/ivory-preferences.css`,
`assets/js/toolkit.js`, and `assets/js/theme.js`. Existing browser checks now
assert first-viewport consent, persistence, short landscape control placement
and modal keyboard containment.

After every change, finish verification and record its outcome before removing
temporary diagnostic scripts, screenshots, raw captures, logs, report folders
and release extraction directories. Keep permanent regression tests, development
tools, source assets, secure connection material, actual rollback backups and
the owner's previous deliverables. Temporary data lives in ignored `.tmp/`;
it must never enter the theme ZIP or public webroot. Resolve cleanup paths and
check they remain within the intended directory before recursive deletion.

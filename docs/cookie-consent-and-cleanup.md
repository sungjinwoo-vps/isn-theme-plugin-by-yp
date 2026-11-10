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

Verified November 10, 2026: preview passed 90 suite checks, then 12 focused
checks and 14 final Chromium/WebKit resize/dialog cases. Production passed 88
checks, with four preview-only mail/acceptance checks skipped, plus all 14
additional cross-browser cases. All 82 deployed PHP files passed syntax checks;
the 235-file release and 14 frontend assets match source. Thirteen preserved
state fields match before/after deployment. Production debug flags are off.

Temporary local/server diagnostics and test reports were removed after recording
these results. Backups, private connection files, historical inventories and
the owner's previous traffic report were preserved. Preview processes were
stopped; the standard preview-start tool can restore the development preview.

# Source collection reliability — November 16, 2026

Release 0.3.10 fixes measured collection failures without changing the visual
design, stored article text, editorial schema, mail configuration or URLs.
The owner's separately requested article rewrites and editorial corrections
remain review-only deliverables, outside this public release.

## Confirmed causes

- CISA's official advisory feed returned HTTP 200, RSS/XML and 30 valid entries,
  but its 5,560,692-byte body exceeded the old 5 MiB reader. Production health
  recorded 186 consecutive failed attempts at the pre-change checkpoint.
- NVD intermittently returned HTTP 503 or timed out at the old 14-second limit.
  A failed HIGH partition could previously be hidden by successful CRITICAL
  entries, incorrectly marking a partial collection healthy.
- Source warnings were throttled for 12 hours but were not tied to a persistent
  incident, so the same unresolved outage generated repeated notifications.

## Implementation

The CISA-specific fallback now reads no more than 12 MiB plus one overflow
sentinel byte. It accepts only the exact official HTTPS advisory endpoint,
keeps TLS verification, disables redirects and requires a successful HTTP/XML
response before SimplePie parsing. Empty, blocked and oversized responses remain
errors; the safety limit is not removed.

NVD requests use a 30-second timeout, a bounded response and conservative
six-second request-start pacing. Failures retry on a later scheduled collection
with 15/30/60-minute backoff. Valid provider Retry-After values are respected up
to a one-day bound. A failed severity partition retains the complete prior
last-good collection rather than presenting a partial result as fully fresh.
An ordinary source retry skip preserves actual attempt and success timestamps.

Repeated critical-source failures generate one accepted warning per incident.
A failed notification transport can retry after an hour. A genuinely successful
previously notified incident receives one recovery attempt. Existing legacy
warning throttles migrate without assuming their old transport succeeded.
Later distinct outages can still notify. Overall freshness also remains false
when an unavailable source has no saved fallback entries.

## Validation before release

- 57 transport/source-health fixtures passed, including bounds, HTTP/MIME,
  allowlist/TLS, stale-data preservation, backoff, Retry-After, notification
  failure/retry, legacy migration and complete NVD partition handling.
- All 93 existing source-evidence/reviewed-vendor fixtures passed.
- Changed PHP syntax and focused PHPStan passed.
- The isolated preview parsed the actual 30-entry CISA response and collected
  135 NVD entries in 9.74 seconds. Its NVD exception existed only in that CLI
  process; mail, automatic publishing and normal preview request blockers
  remained installed.
- All 106 existing preview browser checks passed. A separate audit identified
  a pre-existing newsletter privacy-link styling issue on article pages; that
  appearance change is an unapplied review proposal, not hidden by these tests.
- A restricted database/theme rollback backup was verified before deployment.
  Exact backup paths and checksums remain in private deployment records.

## Production verification — November 17

The existing native automatic theme-update channel installed 0.3.10 after the
verified backup and November 16 release push. It was already present at the
November 17 check and was not reinstalled just to repeat deployment. WordPress
remains 7.1.4. Release code is `724c71faebcefb162223a5528928118569423760`,
successful Actions run `43891942725`. Published theme ZIP SHA256:
`fa281bdc06f777eeb42fd63c49d7b8c0bc34de4c4c414886884fb07f29204494`.

All 239 installed files match the official ZIP; all 239 package entries match
source after expected CI endpoint/newline normalization. All 15 served CSS/JS
assets match the package and all 85 deployed PHP files pass syntax checks.
Latest saved successful source checks show seven CISA entries and 122 NVD
records, both with zero failures. The full NVD collector took 23.447 seconds
across both partitions and pacing. That total does not establish the duration
of either individual request; the 30-second per-request limit is verified by
the transport fixtures.

Private Toolkit options, theme mods, active plugins and branding match the
pre-change checkpoint. Counts remain 255 posts, six pages, 159 staged and five
superseded. All standalone/static bodies, identities, dates and history match;
only the scheduled rolling hub 1169 refreshed through its normal automation.
No article migration, manual editorial rewrite, design change or DNS/mail-provider
change was performed.

Production browser suite results: 101 passed, four preview-only checks skipped,
one initial desktop footer-position timing failure. The same unchanged test then
passed three focused reruns. Do not describe the initial suite as entirely green.
Additional tablet and dark/light contexts preserve layout and have no horizontal
overflow or JavaScript errors. Existing privacy-link accessibility and tablet blog
layout-shift findings are documented as review proposals, rather than silently
changing the owner's requested appearance.

A healthy check does not guarantee that an external provider will never fail
again. Later failures remain visible and use last-good data and incident-based
alerts. Private analytics and unpublished editorial work remain owner
deliverables outside the public repository.

After recording results, temporary diagnostic captures/scripts/logs/test reports
were removed locally and from the server: 202 local files plus the cleanup
helper, 11 remote helper files and three fixture/cache directories. The isolated
preview server/tunnel were stopped. Owner reports, actual rollback backups,
secure connections, permanent quality tools/development databases and tracked
distribution assets were preserved. No top-level remote `isn-*` helper file
remains. Public home and live-brief checks still return HTTP200 after cleanup.

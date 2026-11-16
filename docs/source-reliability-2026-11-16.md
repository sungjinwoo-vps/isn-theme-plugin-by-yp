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

Production package verification, preservation checks and cleanup results are
recorded below after rollout. A healthy check does not guarantee that an external
provider will never fail again; later failures remain visible and use last-good
data and incident-based alerts.

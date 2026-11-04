# InfoSecNexus article evidence standard

The Atlasum Linux IPv4 TCP article was read in full on November 4, 2026 as an
editorial reference: https://atlasum.net/blog/cve-2026-98432-linux-ipv4-tcp-uaf.
Adopt its evidence-led approach and useful structure; write original articles.
Do not copy its prose, claim its tests as our own, or reuse its vulnerability
facts without checking the primary records.

## Every article

- Explain the development, why it matters to the named technology, affected
  readers or systems, the technical context, appropriate actions, and how to
  check the result. Use source-specific facts instead of repeated padding.
- Link directly to the official advisory, release, source record, or research
  beside the associated claim and list the primary references at the end.
- Distinguish source statements, editorial operational advice, and unknowns.
  State missing evidence honestly; do not invent versions, commands, indicators,
  exploit status, deadlines, quotations, test results, or severity scores.
- Keep publication and actual update dates clear, with a useful article guide,
  readable tables and code, related briefings, and a real correction route.
- Change history describes actual article-body changes. Source checks, cache
  refreshes, unchanged cron runs, and deployment alone are not editorial updates.

## Vulnerability articles

Use a concise overview and a key-facts table, followed by affected scope,
technical explanation, exposure checks, permanent remediation, temporary
mitigation when one exists, validation, exploitation evidence, timeline, and
primary sources. Combine or omit sections when the source has no relevant facts.

Preserve the CVSS version, vector, assigning organization, and score together.
Different organizations can disagree. A KEV listing is an exploitation signal,
not a CVSS score. A public proof of concept is not proof of exploitation in the
wild. Missing KEV evidence does not prove an issue is safe.

Version tables should identify products, release branches, affected ranges,
fixed builds, and status with direct attribution. Distribution backports must
not be compared solely with upstream version numbers. Keep advisory dates and
KEV dates distinct from the original vulnerability publication date.

Commands must have a defensible connection to the affected product and explain
what their output establishes. Do not imply a version query detects compromise.
Temporary mitigations must carry their limits, persistence, and rollback when
the primary guidance supplies them. Do not describe a workaround as a fix.

## Other blog content

General news, cloud changes, AI security, Linux/DevOps guides, and tutorials
follow the same evidence standard with topic-appropriate sections: development,
verified facts, technical implications, prerequisites/scope, actions, validation,
limitations, timeline, and references. Do not force CVE fields into unrelated
stories or inflate articles to an arbitrary word count.

## Persistence and publication

Publication stores normalized evidence in `_infosecnexus_editorial_evidence`,
the last generated body SHA256 in `_infosecnexus_editorial_body_hash`, and real
body-change entries in `_infosecnexus_editorial_history`. Evidence fetching runs
during cron, an explicit refresh, or the migration command, never article reads.

Automation compares the stored body hash before updating an existing article.
A manually changed body is preserved. Editors can also set
`_infosecnexus_editorial_manual=1` to keep a managed article out of automation.
Before the first hash exists, only an active importer-managed post with a live
generator fingerprint, no recorded WordPress editor save, and the recognized
legacy article outline is eligible.
Staged posts and superseded rolling redirects are excluded.

## Historical upgrade

Back up the WordPress database and theme first. The command defaults to a dry
run and does not modify article content, metadata, publication dates, taxonomies,
images, or URLs until `--apply` is supplied. Fetch caches may warm during review.

```bash
wp infosecnexus newsroom upgrade-articles
wp infosecnexus newsroom upgrade-articles --post-id=123
wp infosecnexus newsroom upgrade-articles --apply
wp infosecnexus newsroom upgrade-articles --apply --limit=10
```

Review checked, eligible, updated, unchanged, skipped, and failed totals and
per-post reasons. The migration looks beyond the recent feed window: it uses
stored evidence when available, otherwise matches each historical CVE against
the complete official CISA KEV catalog, then enriches it from its CNA record.
It updates only the article body, source-reference metadata, generated evidence,
fingerprint, and actual change history. Original titles, URLs, publication dates,
taxonomies, and images are preserved. Existing non-CVE stories use stored
normalized evidence when available. The two legacy Cisco advisories can recover
their exact source-summary paragraph and stored direct HTTPS Cisco reference;
the migration does not infer a source publication date or exploitation claim.
Other unsupported legacy news is preserved for review. Rolling content is
rebuilt by its normal source-backed publication flow.

The reviewed source snapshots for the two historical grouped Cisco advisories
are in `docs/editorial-source-notes-2026-11-04.json`. Their dates, scope, fixed
releases, affected hot-fix exceptions, and narrow exploitation statements were
checked against both full official Cisco advisories. They provide a reviewable
one-time migration input; no per-post facts are hardcoded in the runtime engine.

After applying, inspect representative old CVEs and the current rolling brief
as an anonymous visitor on desktop and mobile. Re-run the migration to confirm
unchanged bodies do not acquire new history entries or update timestamps.

# Reviewed editorial and search metadata implementation

Release 0.3.11 supports the owner's approved next publishing batch: the permanent
Live Cybersecurity Brief and the existing Ray and OpenSSH articles. The private
reviewed publication payload and source checks remain owner deliverables outside
the public repository. The release itself does not run a historical migration.

## Resulting behavior

The rolling brief starts with a semantic priority table, separates exploitation
from severity, presents each item's narrative once and reports source availability
for its recorded snapshot. Active exact-CVE articles and explicitly selected
current remediation guides are linked using their current WordPress permalinks.
Retired, superseded, password-protected and rolling records are excluded from
standalone guide selection. Source check timestamps alone do not force a rewrite;
source health changes and guide selections participate in the rolling fingerprint.

Correlating independent vendor advisories now keeps the selected primary vendor's
headline, product context, narrative, scalar assessment and source identity together.
Attributed evidence and references remain available. Cisco's explicit Security
Impact Rating and Palo Alto's explicit severity title suffix are parsed without
treating ordinary phrases such as "critical operations" as severity evidence.
This does not claim a complete redesign of product-level vulnerability correlation.

Reviewed search titles apply to current public posts when no SEO plugin owns that
output. Visible article headings remain independently editable. An explicit manual
description flag preserves a reviewed description across normal newsroom refreshes.
The rolling hub remains automated; the two reviewed standalone articles are marked
manual so scheduled generation cannot silently overwrite their reviewed bodies.

The controlled publication retains original URLs, publication dates, author,
taxonomy, featured images and retirement state. Each actual body revision receives
one truthful history entry and updated citations. The selected guides contain
source-grounded applicability, inventory, remediation and validation details;
they do not claim an exploit test or a lab result. Repeating the same publication
is a no-op for article bodies, modification dates and history.

## Failure-path correction

A regression was discovered in the final 0.3.10 source: its provider-error handler
referenced a removed fallback variable. That could stop collection on failure,
even when earlier saved health records showed success. 0.3.11 restores last-good
fallback retrieval and explicitly initializes the bounded CISA reader's response
headers. Final source tests now cover the restored failure path. The older
validation record has been corrected; a saved success is not evidence that a
later provider failure was handled successfully.

## Staging validation

- 112 browser checks passed, including six new publication/metadata cases.
- 57 source reliability fixtures passed against the final corrected collector.
- 87 rolling/editorial checks passed, including 63 shared evidence checks.
- 93 reviewed-vendor/evidence checks and 10 metadata checks passed. Shared
  evidence checks overlap between suites and are not additive independent tests.
- All 85 theme PHP files passed syntax checks; focused PHPStan and JS/CSS lint passed.
- Desktop/light, tablet/dark and mobile/light screenshots were inspected: one H1,
  semantic tables, no horizontal page overflow and no JavaScript errors. Wide
  tables retain the existing locally scrollable reader behavior on mobile.
- Repeating the controlled staging publication left bodies and history unchanged.

A restricted database export and complete theme archive were checksum-verified
before the release push. Exact private paths/checksums remain in deployment records.
Production results are recorded separately in PROJECT-MEMORY.md after verification.
Existing unrelated privacy-link accessibility and tablet blog layout-shift findings
remain review proposals. No traffic or ranking improvement is asserted without
subsequent Search Console measurement.

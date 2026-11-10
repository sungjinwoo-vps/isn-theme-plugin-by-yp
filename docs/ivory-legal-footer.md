# Static pages and footer alignment

Release 0.3.6 removes the blog article guide from Privacy Policy, Terms and
Conditions, and Disclaimer. Static pages use one content wrapper; legal content
has a centered column up to 900px wide. Legal text, heading IDs, URLs and page
metadata are preserved. Blog posts retain their responsive article guide.

The shared footer places legal navigation above copyright, both centered at
every width. Up to 1100px, the brand spans the first row and the two navigation
groups occupy separate columns: security categories on the left, site pages on
the right. Links remain vertical in those groups, and legal links wrap in a
centered row. Configured WordPress menus and fallback menus use the same styles.

Ownership:

- `template-parts/content-page.php`: static content rendering without a guide.
- `inc/footer-builder.php`: legal navigation before copyright in document order.
- `assets/css/ivory-pages.css`: reading column and shared footer alignment.
- Existing shell and footer browser checks assert guide absence, retained legal
  headings, centered bottom text and opposite navigation-column edges.

Validate desktop, tablet and mobile in both appearance modes, including narrow
320px screens. Retain the article guide tests to catch unintended post changes.
Production updates use a fresh database/theme backup and the verified release
archive; do not run content migrations or reset settings for this visual change.

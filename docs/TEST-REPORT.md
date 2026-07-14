# Test Report

Date: 2026-07-14

## Environment

- Remote host verified: `yp@Yashs-iMac.local`, macOS 15.7.7, `x86_64`.
- Docker Desktop verified: Docker 29.5.2, Compose v5.1.4, `hello-world` passed on amd64.
- WordPress stack: WordPress 7.0.1, PHP 8.3.32, MariaDB 11.4, loopback bind `127.0.0.1:8888`.
- Elementor Free installed from WordPress.org: 4.1.4.

## Results

- PHP syntax: passed for all theme/toolkit PHP files.
- PHPCS WordPress Coding Standards: passed.
- PHPStan level 3 with WordPress/Elementor/Woo stubs: passed.
- JavaScript lint: passed.
- CSS lint: passed.
- Playwright desktop/mobile smoke tests: 6 passed.
- Axe via Playwright smoke tests: no serious or critical violations.
- Theme Check: passed. One informational text-domain note confirmed `infosecnexus`.
- Plugin Check: passed with no errors.
- npm audit: 0 vulnerabilities.
- composer audit: no security vulnerability advisories.
- Lighthouse report written: Performance 99, Accessibility 100, Best Practices 100, SEO 91. Lighthouse exited nonzero during Chrome temp cleanup on Windows after writing the report.
- Clean ZIP install test: passed in isolated `infosecnexus_ziptest2` WordPress volume. Theme and plugin installed from `dist` ZIPs, activated, and returned HTTP 200.

## Release Artifacts

- `dist/infosecnexus-theme.zip`
- `dist/infosecnexus-toolkit.zip`
- `dist/infosecnexus-elementor-kit.zip`
- `dist/SHA256SUMS`
- `dist/infosecnexus.sbom.json`

## Notes

- The supplied article body was missing from the prompt, so post ID 5 was created as a draft placeholder that flags the missing article/citations instead of inventing content.
- Trivy was not available on the Windows or iMac PATH, so Trivy scanning was not run.


<?php
/**
 * Dated, explicitly reviewed vendor evidence for individual CVE briefings.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Toolkit;

/** Preserve vendor decisions separately from the CNA's structured record. */
final class Reviewed_Vendor_Evidence {

	/** Remove only this layer's marked rows before rebuilding it deterministically. */
	public static function strip( array $item ): array {
		foreach ( array( 'affected_versions', 'metrics', 'references' ) as $field ) {
			if ( isset( $item[ $field ] ) && is_array( $item[ $field ] ) ) {
				$item[ $field ] = array_values( array_filter( $item[ $field ], static fn( $row ): bool => ! is_array( $row ) || empty( $row['reviewed_vendor_cve'] ) ) );
			}
		}
		unset( $item['vendor_review'] );
		return $item;
	}

	/** Apply only to a dedicated, matching identifier and product, never a bundle. */
	public static function apply( array $item ): array {
		$item = self::strip( $item );
		$cve = strtoupper( trim( (string) ( $item['cve'] ?? '' ) ) );
		$review = self::records()[ $cve ] ?? null;
		if ( ! is_array( $review ) ) {
			return $item;
		}
		preg_match_all( '/\bCVE-\d{4}-\d{4,}\b/i', (string) ( $item['title'] ?? '' ), $matches );
		$identifiers = array_merge( (array) ( $matches[0] ?? array() ), (array) ( $item['related_cves'] ?? array() ) );
		foreach ( $identifiers as $identifier ) {
			if ( $cve !== strtoupper( trim( (string) $identifier ) ) ) {
				return $item;
			}
		}
		$product_text = implode( ' ', array( (string) ( $item['title'] ?? '' ), (string) ( $item['vendor'] ?? '' ), (string) ( $item['product'] ?? '' ) ) );
		if ( 1 !== preg_match( $review['product_pattern'], $product_text ) ) {
			return $item;
		}
		unset( $review['product_pattern'] );
		foreach ( array( 'affected_versions', 'metrics' ) as $field ) {
			foreach ( (array) ( $review[ $field ] ?? array() ) as $row ) {
				$row['source'] = $review['source'];
				$row['url'] = $review['url'];
				$row['reviewed_vendor_cve'] = $cve;
				$item[ $field ][] = $row;
			}
		}
		$known_urls = array_column( (array) ( $item['references'] ?? array() ), 'url' );
		foreach ( array_merge( array( array( 'title' => $review['source'], 'url' => $review['url'] ) ), (array) ( $review['additional_sources'] ?? array() ) ) as $reference ) {
			if ( ! in_array( $reference['url'], $known_urls, true ) ) {
				$item['references'][] = array( 'source' => 'Reviewed vendor guidance', 'title' => $reference['title'], 'url' => $reference['url'], 'reviewed_vendor_cve' => $cve );
				$known_urls[] = $reference['url'];
			}
		}
		// Never replace the CNA's description/solutions, existing score, or KEV state.
		$item['vendor_review'] = $review;
		return $item;
	}

	/** Facts below were checked against these public vendor pages on the stated date. */
	private static function records(): array {
		return array(
			'CVE-2026-19868' => array(
				'product_pattern' => '/\b(?:SonicWall|SonicOS)\b/i',
				'reviewed' => '2026-11-10',
				'source' => 'SonicWall PSIRT SNWLID-2026-0014',
				'url' => 'https://psirt.global.sonicwall.com/vuln-detail/SNWLID-2026-0014',
				'source_revision' => 'Vendor advisory updated November 9, 2026.',
				'affected_versions' => array(
					array( 'product' => 'SonicOS Gen7 (vendor-listed hardware and NSv platforms)', 'affected' => '7.3.3-7015 and older versions', 'status' => 'affected', 'fixed' => '7.3.4-7013 and higher versions' ),
					array( 'product' => 'SonicOS Gen8 (vendor-listed hardware and NSv platforms)', 'affected' => '8.2.2-8015 and older versions', 'status' => 'affected', 'fixed' => '8.2.3-8015 and higher versions' ),
				),
				'metrics' => array( array( 'score' => 7.1, 'version' => '3.1', 'severity' => 'HIGH', 'vector' => 'CVSS:3.1/AV:N/AC:H/PR:L/UI:N/S:C/C:H/I:L/A:N' ) ),
				'applicability' => 'This CVE affects SSLVPN logins with SAML enabled. Exploitation requires valid credentials for an alternate authentication method; SAML-enforced MFA may then be bypassed.',
				'remediation' => 'Use the fixed release for the installed generation: Gen7 7.3.4-7013 or higher, or Gen8 8.2.3-8015 or higher.',
				'workaround' => 'If SAML is used and firmware cannot be upgraded, SonicWall directs administrators to disable or delete active LDAP, RADIUS and TACACS+ authentication servers configured on the firewall. Review authentication dependencies before making this temporary change.',
				'inventory' => array( 'Record the appliance generation and running firmware.', 'Check whether SAML is enabled for SSLVPN and which alternate authentication servers are active.' ),
				'actions' => array( 'Match each appliance to the vendor platform table before choosing firmware.', 'Plan the supported upgrade or documented authentication workaround with a recovery path.', 'Retain the configuration and change evidence for each affected appliance.' ),
				'validation' => array( 'Confirm the running build on each appliance matches its fixed generation.', 'Verify approved SAML/MFA access still works after the change.', 'If using the workaround, verify the affected alternate authentication servers are disabled and record its expiry.' ),
			),
			'CVE-2026-86218' => array(
				'product_pattern' => '/\b(?:N-able|N-central)\b/i',
				'reviewed' => '2026-11-10',
				'source' => 'N-able N-central 2026.3 Hotfix 4 notice',
				'url' => 'https://status.n-able.com/2026/09/06/n-central-2026-3-hotfix-4-cve-2026-86218/',
				'source_revision' => 'Vendor release notice published September 6, 2026.',
				'affected_versions' => array( array( 'product' => 'N-able N-central server', 'affected' => 'Self-hosted deployments; see the CNA range above', 'status' => 'vendor remediation', 'fixed' => '2026.3.1.14 (2026.3 Hotfix 4)' ) ),
				'applicability' => 'Self-hosted N-central requires a server upgrade. The September 6 vendor notice states that hosted NCOD instances had already been patched.',
				'remediation' => 'The fixed server build is 2026.3.1.14. The notice supports direct upgrades from 2025.4, 2026.1, 2026.2, 2026.3 and its Hotfix 1/2 releases; older installations need the documented intermediate path. Agent upgrades are not required for this CVE fix.',
				'workaround' => 'The hotfix notice does not document a workaround; it directs self-hosted customers to upgrade.',
				'exploitation_note' => 'The vendor notice predates the September 8 CISA KEV addition. Its earlier statement about unconfirmed exploitation does not negate the later KEV evidence.',
				'inventory' => array( 'Identify hosted versus self-hosted service and record the running server build.', 'Check the supported upgrade path for older installations.' ),
				'actions' => array( 'Use the vendor release and upgrade documentation for the server change.', 'For legacy builds, follow the supported intermediate upgrades rather than skipping directly.', 'Retain server upgrade and service-health evidence; treat agent maintenance separately.' ),
				'validation' => array( 'Confirm the server runs the fixed build or an applicable supported later release.', 'Verify server health and agent connectivity after the change.', 'Keep earlier exploitation review separate from patch success.' ),
				'additional_sources' => array(
					array( 'title' => 'N-central upgrade documentation', 'url' => 'https://documentation.n-able.com/N-central/userguide/Content/ReleaseDocs/Install_Config/InstallConfig_Upgrade.htm' ),
					array( 'title' => 'N-central supported upgrade paths', 'url' => 'https://documentation.n-able.com/N-central/userguide/Content/ReleaseDocs/Release_Notes/upgrade_path.htm' ),
				),
			),
		);
	}
}

<?php
/** Run: php tests/php/reviewed-vendor-evidence.php [path/to/class-live-intelligence.php]. */
declare(strict_types=1);

// Reuse the standalone WP/source stubs and run the general evidence regression suite.
require __DIR__ . '/newsroom-content.php';

use InfoSecNexus\Theme\Toolkit\Live_Intelligence;
use InfoSecNexus\Theme\Toolkit\Reviewed_Vendor_Evidence;

$sonic = array(
	'cve' => 'CVE-2026-19868', 'id' => 'cve-cve-2026-19868',
	'title' => 'CVE-2026-19868: SonicWall SonicOS SSLVPN authentication',
	'vendor' => 'SonicWall', 'product' => 'SonicOS SSLVPN',
	'description' => 'Authentication enforcement affecting SAML SSLVPN.',
	'source' => 'Fixture government record', 'url' => 'https://fixture.invalid/kev/19868',
	'technical_source' => 'Fixture CNA', 'technical_url' => 'https://www.cve.org/CVERecord?id=CVE-2026-19868',
	'active_exploitation' => true, 'score' => 9.4,
	'metrics' => array( array( 'score' => 9.4, 'version' => '3.1', 'source' => 'Fixture existing scoring authority', 'url' => 'https://fixture.invalid/metric' ) ),
	'affected_versions' => array( array( 'product' => 'SonicWall SonicOS', 'affected' => 'CNA range retained', 'status' => 'unknown', 'source' => 'Fixture CNA', 'url' => 'https://www.cve.org/CVERecord?id=CVE-2026-19868' ) ),
);
$first = Live_Intelligence::article_for_item( $sonic, 'November 10, 2026' );
check( str_contains( $first['content'], '7.3.4-7013 and higher' ) && str_contains( $first['content'], '8.2.3-8015 and higher' ), 'Vendor fixed Gen7 and Gen8 branches must be explicit.' );
check( str_contains( $first['content'], 'LDAP, RADIUS and TACACS+' ) && ! str_contains( $first['content'], 'No product-specific workaround is established' ), 'The published SonicWall workaround must replace the unsupported generic absence claim.' );
check( str_contains( $first['content'], 'SAML enabled' ) && str_contains( $first['content'], 'November 9, 2026' ) && str_contains( $first['content'], '2026-11-10' ), 'Applicability, vendor revision and actual review date must remain distinct.' );
check( str_contains( $first['content'], 'CNA range retained' ) && 9.4 === $first['item']['score'] && $first['item']['active_exploitation'], 'Vendor evidence must preserve CNA claims, existing score and government exploitation evidence.' );
check( 7.1 === $first['item']['metrics'][1]['score'] && 'SonicWall PSIRT SNWLID-2026-0014' === $first['item']['metrics'][1]['source'], 'Use the CVE-specific vendor metric, not the grouped advisory headline8.8.' );
check( str_contains( $first['content'], 'psirt.global.sonicwall.com/vuln-detail/SNWLID-2026-0014' ), 'Vendor guidance must link its own source, not a CNA URL mislabeled as vendor evidence.' );
for ( $iteration = 0; $iteration < 3; ++$iteration ) {
	$again = Live_Intelligence::article_for_item( $first['item'], 'November 10, 2026' );
	check( $first['content'] === $again['content'] && $first['fingerprint'] === $again['fingerprint'], 'Repeated rendering must not accumulate vendor rows/references or alter the body/fingerprint.' );
	check( 3 === count( $again['item']['affected_versions'] ) && 2 === count( $again['item']['metrics'] ), 'Reviewed rows must be rebuilt exactly once.' );
	$first = $again;
}
$fixture_responses['https://cveawg.mitre.org/api/cve/CVE-2026-19868'] = array(
	'response' => array( 'code' => 200 ),
	'body' => json_encode( array(
		'cveMetadata' => array( 'cveId' => 'CVE-2026-19868', 'state' => 'PUBLISHED', 'dateUpdated' => '2026-11-07T04:55:51Z' ),
		'containers' => array( 'cna' => array(
			'providerMetadata' => array( 'shortName' => 'sonicwall' ),
			'descriptions' => array( array( 'lang' => 'en', 'value' => 'SonicOS SSLVPN authentication enforcement.' ) ),
			'affected' => array( array( 'vendor' => 'SonicWall', 'product' => 'SonicOS', 'versions' => array( array( 'version' => '7.3.3-7015 and older versions', 'status' => 'affected' ) ) ) ),
			'references' => array( array( 'url' => 'https://psirt.global.sonicwall.com/vuln-detail/SNWLID-2026-0014' ) ),
		) ),
	) ),
);
$refreshed = Live_Intelligence::enrich_cve_item( $first['item'], true );
$refreshed_article = Live_Intelligence::article_for_item( $refreshed, 'November 10, 2026' );
for ( $iteration = 0; $iteration < 3; ++$iteration ) {
	$refreshed = Live_Intelligence::enrich_cve_item( $refreshed_article['item'], true );
	$next = Live_Intelligence::article_for_item( $refreshed, 'November 10, 2026' );
	check( $refreshed_article['content'] === $next['content'] && $refreshed_article['fingerprint'] === $next['fingerprint'], 'CNA refresh followed by render must preserve reviewed facts without alias/row churn.' );
	check( 1 === count( array_filter( $next['item']['metrics'], static fn( array $metric ): bool => ! empty( $metric['reviewed_vendor_cve'] ) ) ), 'CNA refresh must leave exactly one explicit vendor metric.' );
	$refreshed_article = $next;
}
$wrong = array_replace( $sonic, array( 'cve' => 'CVE-2026-999999', 'title' => 'CVE-2026-999999: SonicWall unrelated issue' ) );
check( empty( Reviewed_Vendor_Evidence::apply( $wrong )['vendor_review'] ), 'A shared vendor is never enough to copy another CVE remediation.' );
$grouped = array_replace( $sonic, array( 'title' => 'SonicWall CVE-2026-19868 and CVE-2026-77708' ) );
check( empty( Reviewed_Vendor_Evidence::apply( $grouped )['vendor_review'] ), 'A grouped advisory must not inherit one member CVE applicability and fix.' );
$related = array_replace( $sonic, array( 'related_cves' => array( 'CVE-2026-19868', 'CVE-2026-77709' ) ) );
check( empty( Reviewed_Vendor_Evidence::apply( $related )['vendor_review'] ), 'Explicit multi-CVE records must remain outside this single-CVE review layer.' );
$mismatched = array_replace( $sonic, array( 'title' => 'CVE-2026-86218: N-able N-central', 'vendor' => 'N-able', 'product' => 'N-central' ) );
check( empty( Reviewed_Vendor_Evidence::apply( $mismatched )['vendor_review'] ), 'Mismatched CVE/title/product identity must not receive reviewed facts.' );
$wrong_product = array_replace( $sonic, array( 'title' => 'CVE-2026-19868: WordPress plugin', 'vendor' => 'WordPress', 'product' => 'Plugin' ) );
check( empty( Reviewed_Vendor_Evidence::apply( $wrong_product )['vendor_review'] ), 'A known CVE paired with the wrong product must fail closed.' );
$same_vendor_other_product = array_replace( $sonic, array( 'title' => 'CVE-2026-19868: SonicWall SMA1000', 'product' => 'SMA1000' ) );
check( empty( Reviewed_Vendor_Evidence::apply( $same_vendor_other_product )['vendor_review'] ), 'A matching vendor cannot substitute for the required SonicOS product family.' );
$stale = array_replace( $first['item'], array( 'cve' => 'CVE-2026-999999', 'title' => 'CVE-2026-999999: unrelated product', 'vendor' => 'Example', 'product' => 'Example' ) );
$cleared = Reviewed_Vendor_Evidence::apply( $stale );
check( empty( $cleared['vendor_review'] ) && 1 === count( $cleared['affected_versions'] ) && 1 === count( $cleared['metrics'] ), 'Changing identity must strip stale marked vendor rows while retaining unrelated source claims.' );
$nable = array_replace( $sonic, array(
	'cve' => 'CVE-2026-86218', 'id' => 'cve-cve-2026-86218', 'title' => 'CVE-2026-86218: N-able N-central server',
	'vendor' => 'N-able', 'product' => 'N-central', 'description' => 'N-central server issue.',
	'affected_versions' => array(), 'metrics' => array(), 'score' => 10.0,
	'technical_url' => 'https://www.cve.org/CVERecord?id=CVE-2026-86218',
) );
$nable_article = Live_Intelligence::article_for_item( $nable, 'November 10, 2026' );
check( str_contains( $nable_article['content'], '2026.3.1.14' ) && str_contains( $nable_article['content'], 'hosted NCOD' ) && str_contains( $nable_article['content'], 'intermediate path' ), 'N-able remediation must distinguish deployment types and supported legacy upgrade path.' );
check( str_contains( $nable_article['content'], 'Agent upgrades are not required' ) && str_contains( $nable_article['content'], 'September 8 CISA KEV' ) && $nable_article['item']['active_exploitation'], 'Server remediation and earlier vendor exploitation wording must not lose later CISA evidence.' );
check( ! str_contains( $nable_article['content'], '7.3.4-7013' ) && ! str_contains( $nable_article['content'], 'TACACS+' ) && 10.0 === $nable_article['item']['score'], 'SonicWall facts and scoring must not leak into N-able.' );
$changed_review = $nable_article['item'];
$changed_review['vendor_review']['reviewed'] = '2026-11-11';
check( call_private( 'items_fingerprint', array( $changed_review ) ) !== call_private( 'items_fingerprint', array( $nable_article['item'] ) ), 'Review metadata is part of the persisted source fingerprint.' );
$repeat = Live_Intelligence::article_for_item( $nable_article['item'], 'November 10, 2026' );
check( $repeat['content'] === $nable_article['content'] && $repeat['fingerprint'] === $nable_article['fingerprint'], 'N-able repeated migration must be idempotent too.' );

echo 'PASS: ' . $checks . " general and reviewed-vendor source-evidence checks\n";

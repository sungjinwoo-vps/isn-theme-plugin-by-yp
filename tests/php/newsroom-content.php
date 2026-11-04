<?php
/** Standalone source-evidence regression fixtures: php tests/php/newsroom-content.php. */
declare(strict_types=1);

define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );
define( 'INFOSECNEXUS_VERSION', 'fixture' );

$fixture_cache = array();
$fixture_responses = array();
$fixture_requests = array();
function get_transient( $key ) { global $fixture_cache; return $fixture_cache[ $key ] ?? false; }
function set_transient( $key, $value, $ttl ) { global $fixture_cache; $fixture_cache[ $key ] = $value; }
function sanitize_text_field( $text ) { return trim( strip_tags( $text ) ); }
function sanitize_key( $text ) { return strtolower( preg_replace( '/[^a-z0-9_-]/i', '', $text ) ); }
function sanitize_title( $text ) { return strtolower( trim( preg_replace( '/[^a-z0-9]+/i', '-', $text ), '-' ) ); }
function wp_strip_all_tags( $text, $breaks = false ) { return strip_tags( $text ); }
function wp_trim_words( $text, $count, $more = '' ) { $words = preg_split( '/\s+/', trim( $text ) ); return count( $words ) > $count ? implode( ' ', array_slice( $words, 0, $count ) ) . $more : $text; }
function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $text ) { return esc_html( $text ); }
function esc_url_raw( $url ) { return preg_match( '~^https?://~i', $url ) ? $url : ''; }
function esc_url( $url ) { return esc_html( esc_url_raw( $url ) ); }
function wp_json_encode( $value ) { return json_encode( $value ); }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function number_format_i18n( $number, $decimals = 0 ) { return number_format( $number, $decimals ); }
function wp_date( $format, $timestamp ) { return gmdate( $format, $timestamp ); }
function home_url( $path ) { return 'https://fixture.invalid' . $path; }
function untrailingslashit( $text ) { return rtrim( $text, '/\\' ); }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function wp_remote_get( $url, $options ) { global $fixture_requests, $fixture_responses; $fixture_requests[] = $url; return $fixture_responses[ $url ] ?? array( 'response' => array( 'code' => 404 ), 'body' => '{}' ); }
function wp_remote_retrieve_response_code( $response ) { return $response['response']['code']; }
function wp_remote_retrieve_body( $response ) { return $response['body']; }
class WP_Error { public function __construct( public string $code, public string $message ) {} public function get_error_message() { return $this->message; } }

require $argv[1] ?? dirname( __DIR__, 2 ) . '/wp-content/themes/infosecnexus/inc/toolkit/class-live-intelligence.php';

use InfoSecNexus\Theme\Toolkit\Live_Intelligence;

$checks = 0;
function check( bool $condition, string $message ): void {
	global $checks;
	++$checks;
	if ( ! $condition ) { throw new RuntimeException( $message ); }
}
function call_private( string $method, ...$args ) {
	return ( new ReflectionMethod( Live_Intelligence::class, $method ) )->invoke( null, ...$args );
}

$seed = array(
	'id' => 'cisa-kev-cve-2026-12345', 'cve' => 'CVE-2026-12345', 'title' => 'CVE-2026-12345: Widget parser flaw',
	'description' => 'A parser can read freed memory when processing an unauthenticated request.',
	'url' => 'https://www.cisa.gov/example', 'source' => 'CISA Known Exploited Vulnerabilities', 'source_key' => 'cisa_kev',
	'published' => gmdate( 'c' ), 'kev_added' => '2026-11-03', 'due_date' => '2026-11-17',
	'active_exploitation' => true, 'type' => 'kev', 'priority' => 120, 'vendor' => 'Example', 'product' => 'Widget',
	'action' => 'Apply the vendor remediation.', 'references' => array(),
);
$record = array(
	'cveMetadata' => array( 'cveId' => $seed['cve'], 'state' => 'PUBLISHED', 'datePublished' => '2026-10-01T10:00:00Z', 'dateUpdated' => '2026-11-03T11:00:00Z' ),
	'containers' => array( 'cna' => array(
		'providerMetadata' => array( 'shortName' => 'Example CNA' ),
		'descriptions' => array( array( 'lang' => 'en', 'value' => 'The request parser releases a shared object before a reader finishes using it. A malformed unauthenticated request can trigger a use-after-free.' ) ),
		'affected' => array( array( 'vendor' => 'Example', 'product' => 'Widget', 'defaultStatus' => 'unaffected', 'versions' => array( array( 'version' => '1.0', 'lessThan' => '2.0', 'versionType' => 'semver', 'status' => 'affected', 'changes' => array( array( 'at' => '1.9.5', 'status' => 'unaffected' ) ) ) ) ) ),
		'problemTypes' => array( array( 'descriptions' => array( array( 'lang' => 'en', 'description' => 'CWE-416 Use After Free' ) ) ) ),
		'metrics' => array( array( 'cvssV3_1' => array( 'version' => '3.1', 'baseScore' => 9.8, 'baseSeverity' => 'CRITICAL', 'vectorString' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H' ) ) ),
		'references' => array( array( 'url' => 'https://vendor.example/security/widget', 'name' => 'Vendor advisory' ), array( 'url' => 'javascript:alert(1)' ) ),
		'solutions' => array( array( 'lang' => 'en', 'value' => 'Install the vendor-patched package and restart the service.' ) ),
	) ),
);
$fixture_responses['https://cveawg.mitre.org/api/cve/' . $seed['cve']] = array( 'response' => array( 'code' => 200 ), 'body' => json_encode( $record ) );
$item = Live_Intelligence::enrich_cve_item( $seed );
check( 1 === count( $fixture_requests ), 'CVE enrichment must fetch the fixed CVE endpoint once.' );
check( 9.8 === $item['score'], 'CNA numeric score must survive normalization.' );
check( '3.1' === $item['metrics'][0]['version'], 'Scoring version must be preserved.' );
check( 'CVE CNA: Example CNA' === $item['metrics'][0]['source'], 'CNA authority must stay attached to the score.' );
check( false !== strpos( $item['affected_versions'][0]['affected'], 'status becomes unaffected at 1.9.5' ), 'Conditional version-status changes must not be discarded.' );
check( ! isset( $item['affected_versions'][0]['fixed'] ), 'An affected range boundary must not be invented as a fixed release.' );
check( false !== strpos( $item['technical_description'], 'shared object' ), 'Technical cause must survive source enrichment.' );
check( 0 === count( array_filter( $item['references'], static fn( $reference ) => str_starts_with( $reference['url'], 'javascript:' ) ) ), 'Non-HTTP source URLs must be rejected.' );
Live_Intelligence::enrich_cve_item( $seed );
check( 1 === count( $fixture_requests ), 'Warm enrichment must use the CVE cache.' );
$article = Live_Intelligence::article_for_item( $item, 'November 4, 2026' );
foreach ( array( 'Key facts', 'Who is affected', 'How the vulnerability works', 'How to check your systems', 'Permanent remediation', 'If you cannot patch yet', 'post-fix validation', 'Timeline and exploitation status', 'CVSS:3.1/', 'Example CNA', 'vendor.example/security/widget' ) as $expected ) {
	check( false !== strpos( $article['content'], $expected ), 'Article is missing evidence/section: ' . $expected );
}
check( false !== strpos( $article['content'], 'No product-specific workaround is established' ), 'Missing workaround must be explicit without inventing one.' );
$changed = $item;
$changed['metrics'][0]['vector'] = 'CVSS:3.1/AV:L/AC:L/PR:L/UI:N/S:U/C:H/I:H/A:H';
check( $article['fingerprint'] !== Live_Intelligence::article_for_item( $changed, 'November 4, 2026' )['fingerprint'], 'A source metric revision must change the article fingerprint.' );
check( $article['fingerprint'] === Live_Intelligence::article_for_item( $item, 'November 5, 2026' )['fingerprint'], 'The historical article fingerprint must not change with the migration date.' );
$plain = $seed;
unset( $plain['active_exploitation'], $plain['type'] );
$plain['cve'] = 'CVE-2026-99999';
$unknown = Live_Intelligence::article_for_item( $plain, 'November 4, 2026' )['content'];
check( false !== strpos( $unknown, 'do not establish exploitation in the wild' ), 'Unknown exploitation must not be reported as confirmed.' );
check( false !== strpos( $unknown, 'numeric score is not supplied' ), 'Missing CVSS must not become zero or Critical.' );
$invalid = $seed;
$invalid['cve'] = '../../private';
$invalid['title'] = 'Invalid identifier';
Live_Intelligence::enrich_cve_item( $invalid );
check( 1 === count( $fixture_requests ), 'Invalid identifiers must not cause HTTP requests.' );

$metric = call_private( 'nvd_metric', array( 'id' => $seed['cve'], 'metrics' => array( 'cvssMetricV31' => array( array( 'source' => 'nvd@nist.gov', 'cvssData' => array( 'version' => '3.1', 'baseScore' => 8.1, 'baseSeverity' => 'HIGH', 'vectorString' => 'CVSS:3.1/AV:N/AC:H/PR:N/UI:N/S:U/C:H/I:H/A:H' ) ), array( 'source' => 'vendor@example.invalid', 'cvssData' => array( 'version' => '3.1', 'baseScore' => 9.8, 'baseSeverity' => 'CRITICAL' ) ) ) ) ) );
check( 2 === count( $metric['metrics'] ), 'Different NVD scoring contributors must remain visible.' );
check( 9.8 === $metric['score'], 'Selected headline score must stay attached to its source metric.' );
$merged = call_private( 'merge_items', $item, array_merge( $seed, array( 'source' => 'NVD', 'metrics' => $metric['metrics'], 'affected_versions' => array( array( 'product' => 'Widget', 'affected' => '<2.0', 'source' => 'NVD', 'url' => 'https://nvd.nist.gov/example' ) ) ) ) );
check( 3 === count( $merged['metrics'] ), 'Merging KEV, CNA, and NVD must preserve scoring provenance.' );
check( 3 === count( $merged['affected_versions'] ), 'Merging sources must preserve version ranges and default status.' );
$urls = call_private( 'references_from_text', 'Vendor: https://vendor.example/a; Notes: https://vendor.example/b.', 'CISA-linked vendor guidance' );
check( 2 === count( $urls ) && 'https://vendor.example/a' === $urls[0]['url'], 'CISA notes must retain clean vendor links.' );
$daily = Live_Intelligence::daily_posts( 'November 4, 2026', array( 'items' => array( $item ), 'checked_at' => gmdate( 'c' ) ) );
check( isset( $daily[0]['evidence'][0], $daily[1]['evidence'][0] ), 'Every generated post must expose evidence for persistence.' );
check( $article['fingerprint'] === $daily[1]['fingerprint'], 'Migration and regular cron must agree on the story fingerprint.' );

$revision = $record;
$revision['containers']['cna']['affected'][0]['versions'][0]['lessThan'] = '3.0';
$revision['containers']['cna']['metrics'][0]['cvssV3_1']['baseScore'] = 7.5;
$revised = call_private( 'merge_cve_evidence', $item, $revision );
check( 2 === count( $revised['affected_versions'] ), 'Revising the same CNA must replace its old ranges rather than accumulate stale claims.' );
check( false !== strpos( $revised['affected_versions'][0]['affected'], '< 3.0' ) && false === strpos( $revised['affected_versions'][0]['affected'], '< 2.0' ), 'Revised CNA range must replace the prior range.' );
check( 1 === count( $revised['metrics'] ) && 7.5 === $revised['score'], 'A lowered CNA score must replace its earlier score.' );
check( false !== strpos( $revised['affected_versions'][0]['affected'], 'version type: semver' ), 'Version comparison semantics must survive normalization.' );
check( 'unaffected' === $revised['affected_versions'][1]['status'], 'The CNA default status must remain explicit.' );
$wp_item = array_merge( $seed, array( 'title' => 'WordPress core issue', 'product' => 'WordPress' ) );
$wp_content = Live_Intelligence::article_for_item( $wp_item, 'November 4, 2026' )['content'];
check( false !== strpos( $wp_content, "wp core version\nwp plugin list" ), 'Inventory command blocks must contain actual newlines.' );
$news = $seed;
unset( $news['cve'], $news['active_exploitation'], $news['type'], $news['kev_added'], $news['due_date'], $news['action'] );
$news['source'] = 'Vendor product announcement';
$news['title'] = 'A vendor adds a new audit feature';
$news['description'] = 'The vendor announced an audit feature for account owners.';
$news['technical_description'] = 'Audit events can be exported by account owners to the documented storage integration. The feature requires the audit-reader permission.';
$news_content = Live_Intelligence::article_for_item( $news, 'November 4, 2026' )['content'];
check( false !== strpos( $news_content, 'Technical context' ) && false === strpos( $news_content, 'CVSS' ), 'General news must use the evidence standard without artificial CVE fields.' );
check( 0 === preg_match( '/vulnerab|exploitat|fixed version|patch/i', $news_content ), 'A neutral product announcement must not acquire unsupported vulnerability or exploitation claims.' );
check( false !== strpos( $news_content, 'audit-reader permission' ), 'General news must retain its source-specific technical detail.' );
$news_item_content = call_private( 'render_newsroom_item', $news, array( 'why' => 'Unsupported vulnerability claim', 'verify' => 'Patch the vulnerable path' ) );
check( 0 === preg_match( '/vulnerab|exploitat|fixed version|patch/i', $news_item_content ), 'Neutral items in the rolling brief must not inherit vulnerability-only advice.' );
$deadline_change = $item;
$deadline_change['due_date'] = '2026-11-10';
check( $article['fingerprint'] !== Live_Intelligence::article_for_item( $deadline_change, 'November 4, 2026' )['fingerprint'], 'A CISA deadline correction alone must refresh the article.' );
foreach ( array( 'vendor' => 'Revised vendor', 'product' => 'Revised product', 'categories' => array( 'linux-administration' ), 'related_cves' => array( 'CVE-2026-54321' ) ) as $field => $value ) {
	$revision = $item;
	$revision[ $field ] = $value;
	check( $article['fingerprint'] !== Live_Intelligence::article_for_item( $revision, 'November 4, 2026' )['fingerprint'], 'A rendered fact revision must refresh the article: ' . $field );
}
$timeline = call_private( 'render_source_timeline', array_merge( $item, array( 'cve_published' => '2026-10-01', 'modified' => '2026-10-30', 'published' => '2026-11-03', 'kev_added' => '2026-11-03' ) ) );
check( strpos( $timeline, 'October 1, 2026' ) < strpos( $timeline, 'October 30, 2026' ) && strpos( $timeline, 'October 30, 2026' ) < strpos( $timeline, 'November 3, 2026' ), 'Source timeline must appear in chronological order.' );
check( 2 === substr_count( $timeline, 'November 3, 2026' ), 'Different source events on the same date must both survive.' );
check( '' === call_private( 'render_source_timeline', array() ), 'Unknown source dates must not create an empty timeline.' );
$grouped = $news;
$grouped['affected_versions'] = array( array( 'product' => 'Example Gateway 16', 'affected' => '<16.5.0-780', 'fixed' => '16.5.0-780', 'source' => 'Vendor advisory', 'url' => 'https://vendor.example/advisory' ) );
$grouped['solutions'] = 'Deploy the vendor-listed fixed build after compatibility testing.';
$grouped['workarounds'] = 'The vendor reports no supported workaround for the identified issues.';
$grouped['scoped_exploitation'] = 'The vendor reports exploitation of two named issues in this grouped advisory.';
$grouped_content = Live_Intelligence::article_for_item( $grouped, 'November 4, 2026' )['content'];
check( false !== strpos( $grouped_content, '16.5.0-780' ) && false !== strpos( $grouped_content, $grouped['workarounds'] ) && false !== strpos( $grouped_content, $grouped['scoped_exploitation'] ), 'Grouped advisories must display supplied exact versions, workaround limits, and scoped exploitation evidence.' );

echo 'PASS: ' . $checks . " source-evidence regression checks\n";

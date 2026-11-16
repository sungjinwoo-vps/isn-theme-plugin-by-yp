<?php
/** Standalone transport/health fixtures: php tests/php/source-reliability.php [collector path]. */
declare(strict_types=1);

namespace InfoSecNexus\Theme\Toolkit {
	function time(): int { return (int) $GLOBALS['fixture_clock']; }
	function microtime( bool $as_float = false ) { return $as_float ? $GLOBALS['fixture_clock'] : '0 ' . time(); }
	function gmdate( string $format, ?int $timestamp = null ): string { return \gmdate( $format, $timestamp ?? time() ); }
	function usleep( int $microseconds ): void { $GLOBALS['fixture_sleeps'][] = $microseconds; $GLOBALS['fixture_clock'] += $microseconds / 1000000; }
	function file_get_contents( string $url, bool $include = false, $context = null, int $offset = 0, ?int $length = null ) {
		$GLOBALS['fixture_streams'][] = array( 'url' => $url, 'options' => stream_context_get_options( $context ), 'length' => $length );
		return false;
	}
	final class Mailer { public static function recipient_email(): string { return 'owner@fixture.invalid'; } }
}

namespace {
	use InfoSecNexus\Theme\Toolkit\Live_Intelligence;

	define( 'MINUTE_IN_SECONDS', 60 );
	define( 'HOUR_IN_SECONDS', 3600 );
	define( 'DAY_IN_SECONDS', 86400 );
	define( 'MB_IN_BYTES', 1048576 );
	define( 'INFOSECNEXUS_VERSION', 'fixture' );

	$fixture_clock = 1794830400.0;
	$fixture_transients = $fixture_options = $fixture_requests = $fixture_responses = array();
	$fixture_sleeps = $fixture_streams = $fixture_mail = array();
	$fixture_mail_accept = true;
	$checks = 0;

	function get_transient( $key ) { global $fixture_transients, $fixture_clock; $entry = $fixture_transients[ $key ] ?? null; return $entry && $entry['expires'] > $fixture_clock ? $entry['value'] : false; }
	function set_transient( $key, $value, $ttl ) { global $fixture_transients, $fixture_clock; $fixture_transients[ $key ] = array( 'value' => $value, 'expires' => $fixture_clock + $ttl ); return true; }
	function delete_transient( $key ) { unset( $GLOBALS['fixture_transients'][ $key ] ); return true; }
	function get_option( $key, $default = false ) { return $GLOBALS['fixture_options'][ $key ] ?? $default; }
	function update_option( $key, $value, $autoload = null ) { $GLOBALS['fixture_options'][ $key ] = $value; return true; }
	function wp_mail( $to, $subject, $message ) { $GLOBALS['fixture_mail'][] = array( 'subject' => $subject, 'accepted' => $GLOBALS['fixture_mail_accept'] ); return $GLOBALS['fixture_mail_accept']; }
	function wp_strip_all_tags( $text, $breaks = false ) { return strip_tags( $text ); }
	function wp_trim_words( $text, $count, $more = '' ) { $words = preg_split( '/\s+/', trim( $text ) ); return count( $words ) > $count ? implode( ' ', array_slice( $words, 0, $count ) ) . $more : $text; }
	function sanitize_text_field( $text ) { return trim( strip_tags( $text ) ); }
	function esc_url_raw( $url ) { return preg_match( '~^https?://~i', $url ) ? $url : ''; }
	function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
	function home_url( $path ) { return 'https://fixture.invalid' . $path; }
	function get_bloginfo( $key ) { return 'fixture'; }
	function is_wp_error( $result ) { return $result instanceof WP_Error; }
	function add_query_arg( $args, $url ) { return $url . '?' . http_build_query( $args ); }
	function wp_remote_get( $url, $args ) { $GLOBALS['fixture_requests'][] = array( 'url' => $url, 'args' => $args, 'time' => $GLOBALS['fixture_clock'] ); if ( ! $GLOBALS['fixture_responses'] ) { throw new RuntimeException( 'Unexpected HTTP request: ' . $url ); } return array_shift( $GLOBALS['fixture_responses'] ); }
	function wp_remote_retrieve_response_code( $response ) { return $response['response']['code']; }
	function wp_remote_retrieve_body( $response ) { return $response['body']; }
	function wp_remote_retrieve_header( $response, $header ) { return $response['headers'][ $header ] ?? ''; }
	class WP_Error {
		public function __construct( public string $code, public string $message, public $data = null ) {}
		public function get_error_message() { return $this->message; }
		public function get_error_data() { return $this->data; }
		public function get_error_code() { return $this->code; }
	}
	function check( bool $condition, string $message ): void { ++$GLOBALS['checks']; if ( ! $condition ) { throw new RuntimeException( $message ); } }
	function invoke( string $method, ...$args ) { return ( new ReflectionMethod( Live_Intelligence::class, $method ) )->invoke( null, ...$args ); }
	function response( int $status = 200, array $data = array(), array $headers = array() ): array { return array( 'response' => array( 'code' => $status ), 'body' => json_encode( $data ), 'headers' => $headers ); }
	function advance( int $seconds ): void { $GLOBALS['fixture_clock'] += $seconds; }
	function reset_http(): void { $GLOBALS['fixture_requests'] = $GLOBALS['fixture_responses'] = $GLOBALS['fixture_sleeps'] = array(); delete_transient( 'infosecnexus_nvd_retry_after' ); ( new ReflectionProperty( Live_Intelligence::class, 'nvd_last_request' ) )->setValue( null, 0.0 ); }

	require $argv[1] ?? dirname( __DIR__, 2 ) . '/wp-content/themes/infosecnexus/inc/toolkit/class-live-intelligence.php';

	// The actual Nov16 incident was a valid 5,560,692-byte RSS response, above the old cap.
	$rss_headers = array( 'HTTP/1.1 200 OK', 'Content-Type: application/rss+xml; charset=utf-8' );
	check( true === invoke( 'validate_cisa_feed_response', str_repeat( 'x', 5560692 ), $rss_headers ), 'The observed legitimate CISA response size must fit the bounded reader.' );
	check( true === invoke( 'validate_cisa_feed_response', str_repeat( 'x', 12 * MB_IN_BYTES ), $rss_headers ), 'The exact maximum response size must be allowed.' );
	check( 'cisa_feed_too_large' === invoke( 'validate_cisa_feed_response', str_repeat( 'x', 12 * MB_IN_BYTES + 1 ), $rss_headers )->get_error_code(), 'A one-byte overflow must still fail.' );
	check( 'cisa_feed_unavailable' === invoke( 'validate_cisa_feed_response', '', $rss_headers )->get_error_code(), 'An empty successful response must fail.' );
	foreach ( array( 301, 302, 403, 429, 503 ) as $status ) {
		check( 'cisa_feed_http' === invoke( 'validate_cisa_feed_response', '<rss/>', array( 'HTTP/1.1 ' . $status . ' Status', 'Content-Type: application/rss+xml' ) )->get_error_code(), 'Redirects and failed HTTP responses must not become valid feeds.' );
	}
	check( 'cisa_feed_content_type' === invoke( 'validate_cisa_feed_response', '<html>Access denied</html>', array( 'HTTP/2 200 OK', 'Content-Type: text/html' ) )->get_error_code(), 'A 200 HTML edge response must not become a valid feed.' );
	check( 'cisa_feed_http' === invoke( 'validate_cisa_feed_response', '<rss/>', array() )->get_error_code(), 'Missing HTTP success evidence must fail.' );
	foreach ( array( 'http://www.cisa.gov/cybersecurity-advisories/all.xml', 'https://attacker.invalid/cybersecurity-advisories/all.xml', 'https://www.cisa.gov/other.xml', 'https://user:pass@www.cisa.gov/cybersecurity-advisories/all.xml', 'https://www.cisa.gov:8443/cybersecurity-advisories/all.xml' ) as $url ) {
		check( 'invalid_cisa_feed' === invoke( 'fetch_cisa_feed_fallback', $url )->get_error_code(), 'Only the fixed official HTTPS feed endpoint may use the stream fallback.' );
	}
	check( ! $fixture_streams, 'Rejected endpoints must never make a stream request.' );
	check( 'cisa_feed_unavailable' === invoke( 'fetch_cisa_feed_fallback', 'https://www.cisa.gov/cybersecurity-advisories/all.xml?source=infosecnexus' )->get_error_code(), 'Network failures must stay errors.' );
	$stream = $fixture_streams[0];
	check( 12 * MB_IN_BYTES + 1 === $stream['length'], 'The transport itself must enforce the cap plus overflow sentinel.' );
	check( 0 === $stream['options']['http']['follow_location'] && 0 === $stream['options']['http']['max_redirects'], 'The stream transport must not follow redirects outside the allowlist.' );
	check( true === $stream['options']['ssl']['verify_peer'] && true === $stream['options']['ssl']['verify_peer_name'], 'TLS certificate verification must stay enabled.' );

	// Actual source-health transitions, with no network or mail delivery.
	$key = 'fixture_source';
	$cache_key = 'infosecnexus_live_source_' . md5( $key );
	$last_good_key = 'infosecnexus_live_source_last_good_' . $key;
	$alert_key = 'infosecnexus_live_source_alert_' . md5( $key );
	$old_items = array( array( 'id' => 'old-verified', 'title' => 'Verified last-good advisory' ) );
	$fixture_options[ $last_good_key ] = array( 'checked_at' => '2026-11-15 00:00:00', 'items' => $old_items );
	$callback_count = 0;
	$callback_result = new WP_Error( 'source_http', 'Source returned HTTP 503.' );
	$source = array( 'label' => 'Fixture source', 'critical' => true, 'ttl' => 15 * MINUTE_IN_SECONDS, 'callback' => static function () use ( &$callback_count, &$callback_result ) { ++$callback_count; return $callback_result; } );
	$previous = array( 'last_success' => '2026-11-15 00:00:00' );
	$first = invoke( 'collect_source', $key, $source, false, $previous );
	check( $old_items === $first['items'] && false === $first['status']['ok'] && true === $first['status']['stale'], 'Failure must retain verified data while exposing stale/error health.' );
	check( $previous['last_success'] === $first['health']['last_success'], 'Failure must preserve the actual last-success timestamp.' );
	check( 1 === $first['health']['failure_count'] && ! $fixture_mail, 'The first failure must not send an alert.' );
	check( 900 === $first['health']['next_retry'] - (int) $fixture_clock, 'The initial scheduled retry must respect the source cadence.' );
	$pending = invoke( 'collect_source', $key, $source, false, $first['health'] );
	check( 1 === $callback_count && true === $pending['status']['retry_pending'], 'A backoff skip must not contact the source.' );
	check( $first['health'] === $pending['health'] && ! $fixture_mail, 'A skip must neither increment failure counts nor update check times or send mail.' );
	advance( 900 );
	$second = invoke( 'collect_source', $key, $source, false, $pending['health'] );
	check( 2 === $callback_count && 2 === $second['health']['failure_count'] && 1800 === $second['health']['next_retry'] - (int) $fixture_clock, 'The second actual failure must schedule a longer retry.' );
	advance( 1800 );
	$third = invoke( 'collect_source', $key, $source, false, $second['health'] );
	check( 1 === count( $fixture_mail ) && 'sent' === $third['health']['incident_notification'], 'The third actual failure must send and persist one accepted incident warning.' );
	check( 3600 === $third['health']['next_retry'] - (int) $fixture_clock, 'Failure backoff must be capped at one hour.' );
	advance( 13 * HOUR_IN_SECONDS );
	$fourth = invoke( 'collect_source', $key, $source, false, $third['health'] );
	check( 1 === count( $fixture_mail ) && 4 === $fourth['health']['failure_count'], 'An unresolved incident must not resend just because the old 12-hour window elapsed.' );
	$callback_result = array( array( 'id' => 'fresh-verified', 'title' => 'Fresh verified advisory' ) );
	advance( HOUR_IN_SECONDS );
	$recovered = invoke( 'collect_source', $key, $source, false, $fourth['health'] );
	check( true === $recovered['status']['ok'] && false === $recovered['status']['stale'] && 0 === $recovered['health']['failure_count'], 'Actual recovery must reset failure and retry state.' );
	check( 2 === count( $fixture_mail ) && str_contains( $fixture_mail[1]['subject'], 'source recovered' ), 'A known-notified incident must receive exactly one recovery notice.' );
	check( $callback_result === $fixture_options[ $last_good_key ]['items'] && ! get_transient( $alert_key ), 'Recovery must replace last-good data and reset incident notification throttles.' );
	$again = invoke( 'collect_source', $key, $source, false, $recovered['health'] );
	check( $again['status']['cached'] && 2 === count( $fixture_mail ), 'Cached success must not resend recovery notices.' );
	$callback_result = new WP_Error( 'source_timeout', 'Timed out.' );
	$new_incident = $recovered;
	for ( $i = 0; $i < 3; ++$i ) { $new_incident = invoke( 'collect_source', $key, $source, true, $new_incident['health'] ); }
	check( 3 === count( $fixture_mail ) && 'sent' === $new_incident['health']['incident_notification'], 'A later distinct outage must still alert after three failures.' );
	delete_transient( $alert_key );
	$fixture_mail_accept = false;
	$failed_mail = invoke( 'collect_source', $key, $source, true, array( 'failure_count' => 2 ) );
	check( 'pending' === $failed_mail['health']['incident_notification'], 'Rejected mail transport must not be recorded as a sent warning.' );
	$before_retry = count( $fixture_mail );
	invoke( 'collect_source', $key, $source, true, $failed_mail['health'] );
	check( $before_retry === count( $fixture_mail ), 'Mail failures must have their own retry throttle.' );
	advance( HOUR_IN_SECONDS );
	$fixture_mail_accept = true;
	$retried_mail = invoke( 'collect_source', $key, $source, true, $failed_mail['health'] );
	check( 'sent' === $retried_mail['health']['incident_notification'] && $before_retry + 1 === count( $fixture_mail ), 'A failed warning must retry after the mail cooldown and become sent only on acceptance.' );
	set_transient( $alert_key, '1', 12 * HOUR_IN_SECONDS );
	$legacy_count = count( $fixture_mail );
	$legacy = invoke( 'collect_source', $key, $source, true, array( 'failure_count' => 152 ) );
	advance( 13 * HOUR_IN_SECONDS );
	$legacy_again = invoke( 'collect_source', $key, $source, true, $legacy['health'] );
	check( 'legacy' === $legacy_again['health']['incident_notification'] && $legacy_count === count( $fixture_mail ), 'An existing legacy incident must migrate without recurring 12-hour warnings.' );
	$callback_result = array();
	invoke( 'collect_source', $key, $source, true, $legacy_again['health'] );
	check( $legacy_count === count( $fixture_mail ), 'A legacy notice with unknown transport result must not cause an assumed recovery mail.' );
	$callback_result = new WP_Error( 'source_http', 'Unavailable.' );
	$no_good = invoke( 'collect_source', 'no_previous_data', $source, true, array() );
	check( ! $no_good['items'] && $no_good['status']['stale'] && ! $no_good['status']['ok'], 'An outage without last-good entries must still mark the source stale.' );
	$callback_result = new WP_Error( 'rate_limit', 'Pause.', array( 'retry_after' => 7200 ) );
	$provider_pause = invoke( 'collect_source', 'provider_pause', $source, true, array() );
	check( 7200 === $provider_pause['health']['next_retry'] - (int) $fixture_clock, 'A provider pause may exceed ordinary exponential backoff.' );

	// NVD-only request behavior and the incomplete-severity regression.
	reset_http();
	$nvd_url = 'https://services.nvd.nist.gov/rest/json/cves/2.0/?cvssV3Severity=CRITICAL';
	$fixture_responses = array( response( 200, array( 'vulnerabilities' => array() ) ), response( 200, array( 'vulnerabilities' => array() ) ) );
	invoke( 'fetch_json', $nvd_url );
	invoke( 'fetch_json', $nvd_url );
	check( 2 === count( $fixture_requests ) && $fixture_requests[1]['time'] - $fixture_requests[0]['time'] >= 6, 'Successive NVD requests must be paced by at least six seconds.' );
	check( 30 === $fixture_requests[0]['args']['timeout'] && 12 * MB_IN_BYTES === $fixture_requests[0]['args']['limit_response_size'], 'NVD requests need a realistic timeout and a bounded response.' );
	reset_http();
	$fixture_responses = array( response( 503, array(), array( 'retry-after' => '7200' ) ) );
	$unavailable = invoke( 'fetch_json', $nvd_url );
	check( is_wp_error( $unavailable ) && 7200 === $unavailable->get_error_data()['retry_after'], 'A 503 must expose the provider retry interval.' );
	$paused = invoke( 'fetch_json', $nvd_url );
	check( 1 === count( $fixture_requests ) && 'infosecnexus_nvd_retry_after' === $paused->get_error_code(), 'Even a forced collection must not bypass a provider Retry-After pause.' );
	reset_http();
	$fixture_responses = array( response( 429, array(), array( 'retry-after' => '60' ) ) );
	$limited = invoke( 'fetch_json', $nvd_url );
	check( 60 === $limited->get_error_data()['retry_after'], 'HTTP429 must honor Retry-After as well.' );
	check( 0 === invoke( 'source_retry_after', 'nonsense' ) && 0 === invoke( 'source_retry_after', '-20' ), 'Invalid Retry-After values must not schedule arbitrary pauses.' );
	check( 0 === invoke( 'source_retry_after', \gmdate( 'D, d M Y H:i:s \G\M\T', (int) $fixture_clock - 60 ) ), 'Past HTTP dates must not produce a negative delay.' );
	check( 600 === invoke( 'source_retry_after', \gmdate( 'D, d M Y H:i:s \G\M\T', (int) $fixture_clock + 600 ) ), 'Future HTTP dates must be interpreted as delay seconds.' );
	check( DAY_IN_SECONDS === invoke( 'source_retry_after', '999999999999999999999' ), 'Untrusted enormous retry values must remain bounded.' );
	reset_http();
	$fixture_responses = array( response( 200, array( 'id' => 'ordinary source' ) ) );
	invoke( 'fetch_json', 'https://cveawg.mitre.org/api/cve/CVE-2026-12345' );
	check( 14 === $fixture_requests[0]['args']['timeout'] && ! isset( $fixture_requests[0]['args']['limit_response_size'] ) && ! $fixture_sleeps, 'Unrelated JSON collectors must retain their existing request behavior.' );
	reset_http();
	$critical = array( 'vulnerabilities' => array( array( 'cve' => array( 'id' => 'CVE-2026-12345', 'published' => '2026-11-15T00:00:00Z', 'descriptions' => array( array( 'lang' => 'en', 'value' => 'A verified critical record.' ) ) ) ) ) );
	$fixture_responses = array( response( 200, $critical ), response( 503 ) );
	$partial = invoke( 'collect_nvd' );
	check( is_wp_error( $partial ) && 'infosecnexus_nvd_unavailable' === $partial->get_error_code(), 'Fresh CRITICAL rows must not conceal a failed HIGH partition.' );
	reset_http();
	$fixture_responses = array( response( 503 ) );
	check( is_wp_error( invoke( 'collect_nvd' ) ) && 1 === count( $fixture_requests ), 'An unavailable first NVD partition must not immediately trigger another request.' );
	reset_http();
	$fixture_responses = array( response( 200, array( 'message' => 'Error page disguised as JSON' ) ) );
	check( 'infosecnexus_nvd_payload' === invoke( 'collect_nvd' )->get_error_code(), 'Unexpected JSON must not be accepted as an empty healthy NVD source.' );
	reset_http();
	$fixture_responses = array( response( 200, $critical ), response( 200, array( 'vulnerabilities' => array() ) ) );
	$complete = invoke( 'collect_nvd' );
	check( is_array( $complete ) && 1 === count( $complete ) && 'CVE-2026-12345' === $complete[0]['cve'], 'A complete NVD check must still normalize verified records.' );

	echo 'Source reliability fixtures passed: ' . $checks . PHP_EOL;
}

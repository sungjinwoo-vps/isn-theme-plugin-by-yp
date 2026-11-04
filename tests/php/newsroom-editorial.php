<?php
/** Standalone stateful regression fixture: php tests/php/newsroom-editorial.php. */
declare(strict_types=1);

namespace InfoSecNexus\Theme\Toolkit {
	final class Live_Intelligence {
		public static function content_schema_version(): string { return 'fixture-v1'; }
		public static function enrich_cve_item( array $item ): array { return $item; }
		public static function article_for_item( array $item, string $date ): array {
			$content = $item['fixture_content'] ?? '<h2>Verified guidance</h2><p>' . htmlspecialchars( $item['description'] ) . '</p>';
			return array( 'content' => $content, 'sources' => array( array( 'Vendor', 'https://example.test/advisory' ) ), 'fingerprint' => hash( 'sha256', $content ), 'item' => $item );
		}
	}
	final class Demo_Content {
		public static array $purged = array();
		public static function purge_public_cache( array $ids ): void { self::$purged = array_merge( self::$purged, $ids ); }
	}
}

namespace {
	final class WP_Post {
		public int $ID;
		public string $post_type = 'post';
		public string $post_status = 'publish';
		public string $post_title = 'CVE-2026-12345: fixture';
		public string $post_content = '<p>Before</p>';
		public string $post_name = 'original-permalink';
		public string $post_date = '2026-08-01 12:00:00';
		public string $post_date_gmt = '2026-08-01 06:30:00';
		public array $categories = array( 2, 7 );
		public int $thumbnail = 321;
		public function __construct( int $id ) { $this->ID = $id; }
	}
	$GLOBALS['fixture_posts'] = array();
	$GLOBALS['fixture_meta'] = array();
	$GLOBALS['fixture_writes'] = 0;
	function get_post( int $id ) { return $GLOBALS['fixture_posts'][ $id ] ?? null; }
	function get_post_meta( int $id, string $key, bool $single = true ) { return $GLOBALS['fixture_meta'][ $id ][ $key ] ?? ''; }
	function fixture_unslash( $value ) { return is_array( $value ) ? array_map( 'fixture_unslash', $value ) : ( is_string( $value ) ? stripslashes( $value ) : $value ); }
	function update_post_meta( int $id, string $key, $value ): bool { $GLOBALS['fixture_meta'][ $id ][ $key ] = fixture_unslash( $value ); ++$GLOBALS['fixture_writes']; return true; }
	function wp_slash( $value ) { return is_array( $value ) ? array_map( 'wp_slash', $value ) : ( is_string( $value ) ? addslashes( $value ) : $value ); }
	function wp_json_encode( $value ) { return json_encode( $value, JSON_UNESCAPED_SLASHES ); }
	function wp_strip_all_tags( string $value ): string { return strip_tags( $value ); }
	function esc_attr( string $value ): string { return htmlspecialchars( $value, ENT_QUOTES ); }
	function esc_html( string $value ): string { return htmlspecialchars( $value, ENT_QUOTES ); }
	function esc_url_raw( string $value ): string { return filter_var( $value, FILTER_SANITIZE_URL ); }
	function wp_parse_url( string $value, int $component = -1 ) { return parse_url( $value, $component ); }
	function wp_date( string $format, int $timestamp ): string { return gmdate( $format, $timestamp ); }
	function is_wp_error( $value ): bool { return false; }
	function wp_update_post( array $data, bool $error = false ): int {
		$data = fixture_unslash( $data );
		foreach ( $data as $key => $value ) {
			if ( 'ID' !== $key ) { $GLOBALS['fixture_posts'][ $data['ID'] ]->$key = $value; }
		}
		++$GLOBALS['fixture_writes'];
		return $data['ID'];
	}
	function fixture_meta_matches( int $id, array $query ): bool {
		if ( isset( $query['key'] ) ) {
			$exists = array_key_exists( $query['key'], $GLOBALS['fixture_meta'][ $id ] ?? array() );
			$value = get_post_meta( $id, $query['key'] );
			$compare = $query['compare'] ?? '=';
			if ( 'NOT EXISTS' === $compare ) { return ! $exists; }
			if ( 'IN' === $compare ) { return $exists && in_array( $value, $query['value'], true ); }
			return $exists && $value === $query['value'];
		}
		$answers = array();
		foreach ( $query as $key => $clause ) { if ( is_array( $clause ) ) { $answers[] = fixture_meta_matches( $id, $clause ); } }
		return 'OR' === ( $query['relation'] ?? 'AND' ) ? in_array( true, $answers, true ) : ! in_array( false, $answers, true );
	}
	function get_posts( array $args ): array {
		$posts = array_values( array_filter( $GLOBALS['fixture_posts'], static fn( WP_Post $post ): bool => ( empty( $args['p'] ) || (int) $args['p'] === $post->ID ) && fixture_meta_matches( $post->ID, $args['meta_query'] ) ) );
		usort( $posts, static fn( WP_Post $a, WP_Post $b ): int => $a->ID <=> $b->ID );
		$posts = $args['posts_per_page'] > 0 ? array_slice( $posts, 0, $args['posts_per_page'] ) : $posts;
		return array_map( static fn( WP_Post $post ): WP_Post => clone $post, $posts );
	}
	function expect( bool $passed, string $message ): void { if ( ! $passed ) { throw new \RuntimeException( $message ); } }

	require_once $argv[1] ?? dirname( __DIR__, 2 ) . '/wp-content/themes/infosecnexus/inc/toolkit/class-newsroom-editorial.php';
	use InfoSecNexus\Theme\Toolkit\Newsroom_Editorial;

	foreach ( range( 1, 4 ) as $id ) {
		$GLOBALS['fixture_posts'][ $id ] = new WP_Post( $id );
		$GLOBALS['fixture_meta'][ $id ] = array( '_infosecnexus_newsroom_post' => '1', '_infosecnexus_newsroom_kind' => 'breaking' );
	}
	$GLOBALS['fixture_meta'][1]['_infosecnexus_retirement_state'] = 'staged';
	$GLOBALS['fixture_meta'][2]['_infosecnexus_newsroom_superseded_by'] = '3';
	$evidence = array( array( 'cve' => 'CVE-2026-12345', 'description' => "Vendor's record with path C:\\service", 'fixture_content' => '<h2>Verified facts</h2><p>After</p>' ) );
	Newsroom_Editorial::record_update( 3, $evidence, '' );
	expect( Newsroom_Editorial::evidence( 3 ) === $evidence, 'Evidence JSON must survive WordPress slash handling.' );
	expect( Newsroom_Editorial::can_update( get_post( 3 ) ), 'An unchanged stored body must be eligible.' );
	$original = clone get_post( 3 );
	$writes = $GLOBALS['fixture_writes'];
	$preview = Newsroom_Editorial::upgrade_articles( false, 1 );
	expect( 1 === $preview['counts']['checked'] && 1 === $preview['counts']['eligible'] && 3 === $preview['rows'][0]['id'], 'Limit must apply after retired and superseded filtering.' );
	expect( $GLOBALS['fixture_writes'] === $writes && get_post( 3 )->post_content === $original->post_content, 'Dry runs must not modify posts or their metadata.' );
	$apply = Newsroom_Editorial::upgrade_articles( true, 1 );
	expect( 1 === $apply['counts']['updated'], 'Apply must save an eligible article.' );
	expect( get_post( 3 )->post_name === $original->post_name && get_post( 3 )->post_date === $original->post_date && get_post( 3 )->categories === $original->categories && get_post( 3 )->thumbnail === $original->thumbnail, 'Migration must preserve permalink, publication date, taxonomy and featured image.' );
	expect( 1 === count( get_post_meta( 3, '_infosecnexus_editorial_history', true ) ), 'One changed body must create exactly one history entry.' );
	$writes = $GLOBALS['fixture_writes'];
	$again = Newsroom_Editorial::upgrade_articles( true, 1 );
	expect( 1 === $again['counts']['unchanged'] && $writes === $GLOBALS['fixture_writes'], 'An unchanged rerun must not write or create a new update timestamp.' );
	Newsroom_Editorial::record_update( 3, $evidence, get_post( 3 )->post_content );
	expect( 1 === count( get_post_meta( 3, '_infosecnexus_editorial_history', true ) ), 'A source check must not create a history entry.' );
	expect( str_contains( Newsroom_Editorial::history_html( 3 ), 'id="article-updates"' ), 'History must have the article guide anchor.' );
	get_post( 3 )->post_content .= '<p>Editor correction.</p>';
	expect( ! Newsroom_Editorial::can_update( get_post( 3 ) ), 'A manual body edit must block automation.' );
	$manual = Newsroom_Editorial::upgrade_articles( true, 1 );
	expect( 1 === $manual['counts']['skipped'] && str_contains( get_post( 3 )->post_content, 'Editor correction.' ), 'Migration must retain manual edits.' );
	expect( ! Newsroom_Editorial::can_update( get_post( 1 ) ) && ! Newsroom_Editorial::can_update( get_post( 2 ) ), 'Retired and superseded posts must never be updated.' );
	$legacy = new WP_Post( 8 );
	$legacy->post_title = 'Cisco hardening guidance';
	$legacy->post_content = '<p class="isnx-live-deck">An official source reports active exploitation.</p><p>Exact vendor summary with &amp; character.</p>';
	foreach ( array( 'What changed', 'Why this matters', 'Immediate response plan', 'Detection and validation', 'Accuracy note', 'Primary sources and references' ) as $heading ) { $legacy->post_content .= '<h2>' . $heading . '</h2>'; }
	$legacy->post_content .= '<p>Severity, affected-version ranges, and remediation details can change as the vendor and vulnerability databases add evidence.</p>';
	$GLOBALS['fixture_posts'][8] = $legacy;
	$GLOBALS['fixture_meta'][8] = array( '_infosecnexus_newsroom_post' => '1', '_infosecnexus_demo_content' => '1', '_infosecnexus_live_fingerprint' => 'legacy', '_infosecnexus_source_urls' => json_encode( array( array( 'Cisco', 'https://sec.cloudapps.cisco.com/security/advisory' ) ) ) );
	expect( Newsroom_Editorial::can_update( $legacy ), 'A recognized importer-managed legacy template can receive its first hash.' );
	$GLOBALS['fixture_meta'][8]['_edit_last'] = '2';
	expect( ! Newsroom_Editorial::can_update( $legacy ), 'A recorded editor save must protect a legacy article before its first hash exists.' );
	unset( $GLOBALS['fixture_meta'][8]['_edit_last'] );
	$general = Newsroom_Editorial::upgrade_articles( true, -1, 8 );
	expect( 1 === $general['counts']['updated'], 'Known legacy Cisco non-CVE articles must also upgrade.' );
	$general_evidence = Newsroom_Editorial::evidence( 8 )[0];
	expect( 'Exact vendor summary with & character.' === $general_evidence['description'] && '' === $general_evidence['published'] && '' === $general_evidence['cve'], 'General news must preserve its exact source summary without inventing a publication date or CVE.' );

	fwrite( STDOUT, "Newsroom editorial persistence fixtures passed.\n" );
}

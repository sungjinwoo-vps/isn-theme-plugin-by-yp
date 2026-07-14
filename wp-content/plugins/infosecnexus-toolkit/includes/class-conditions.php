<?php
/**
 * Conditional display engine.
 *
 * @package InfoSecNexusToolkit
 */

declare(strict_types=1);

namespace InfoSecNexus\Toolkit;

/**
 * Conditional matching.
 */
final class Conditions {
	/**
	 * Evaluate condition set.
	 *
	 * @param mixed $conditions Conditions array or JSON.
	 * @return bool
	 */
	public static function matches( $conditions ): bool {
		if ( is_string( $conditions ) ) {
			$conditions = trim( $conditions );
			if ( '' === $conditions ) {
				return true;
			}
			$conditions = json_decode( $conditions, true );
		}

		if ( ! is_array( $conditions ) ) {
			return true;
		}

		$include = is_array( $conditions['include'] ?? null ) ? $conditions['include'] : array( array( 'type' => 'entire_site' ) );
		$exclude = is_array( $conditions['exclude'] ?? null ) ? $conditions['exclude'] : array();

		foreach ( $exclude as $rule ) {
			if ( self::matches_rule( is_array( $rule ) ? $rule : array() ) ) {
				return false;
			}
		}

		foreach ( $include as $rule ) {
			if ( self::matches_rule( is_array( $rule ) ? $rule : array() ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Match one rule.
	 *
	 * @param array<string,mixed> $rule Rule.
	 * @return bool
	 */
	private static function matches_rule( array $rule ): bool {
		$type  = sanitize_key( (string) ( $rule['type'] ?? '' ) );
		$value = $rule['value'] ?? '';

		switch ( $type ) {
			case 'entire_site':
				return true;
			case 'front_page':
				return is_front_page();
			case 'blog_page':
				return is_home();
			case 'posts':
				return is_singular( 'post' );
			case 'pages':
				return is_page();
			case 'category':
			case 'categories':
				return self::term_matches( 'category', $value );
			case 'tag':
			case 'tags':
				return self::term_matches( 'post_tag', $value );
			case 'taxonomy':
			case 'taxonomies':
				return is_tax();
			case 'archives':
				return is_archive();
			case 'search':
				return is_search();
			case '404':
				return is_404();
			case 'custom_post_type':
			case 'cpt':
				return is_singular() && get_post_type() === sanitize_key( (string) $value );
			case 'specific_content':
				return is_singular() && in_array( (string) get_queried_object_id(), self::csv_values( $value ), true );
			case 'parent_page':
				return is_page() && (int) wp_get_post_parent_id( get_queried_object_id() ) === (int) $value;
			case 'logged_in':
				return is_user_logged_in();
			case 'logged_out':
				return ! is_user_logged_in();
			case 'role':
			case 'user_role':
				if ( ! is_user_logged_in() ) {
					return false;
				}
				$user = wp_get_current_user();
				return in_array( sanitize_key( (string) $value ), array_map( 'sanitize_key', (array) $user->roles ), true );
			case 'date_after':
				return time() >= strtotime( (string) $value );
			case 'date_before':
				return time() <= strtotime( (string) $value );
		}

		return false;
	}

	/**
	 * Match taxonomy term by current query or singular post terms.
	 *
	 * @param string $taxonomy Taxonomy.
	 * @param mixed  $value Term slug/name/id or CSV.
	 * @return bool
	 */
	private static function term_matches( string $taxonomy, $value ): bool {
		$values = self::csv_values( $value );
		if ( empty( $values ) ) {
			return is_tax( $taxonomy ) || is_category() || is_tag();
		}

		if ( is_tax( $taxonomy ) || ( 'category' === $taxonomy && is_category() ) || ( 'post_tag' === $taxonomy && is_tag() ) ) {
			$term = get_queried_object();
			return $term && in_array( (string) $term->slug, $values, true );
		}

		if ( is_singular() ) {
			$terms = wp_get_object_terms( get_queried_object_id(), $taxonomy, array( 'fields' => 'slugs' ) );
			return ! is_wp_error( $terms ) && (bool) array_intersect( $values, $terms );
		}

		return false;
	}

	/**
	 * CSV to sanitized string values.
	 *
	 * @param mixed $value Raw value.
	 * @return string[]
	 */
	private static function csv_values( $value ): array {
		if ( is_array( $value ) ) {
			return array_map( 'sanitize_key', array_map( 'strval', $value ) );
		}

		return array_filter( array_map( 'sanitize_key', array_map( 'trim', explode( ',', (string) $value ) ) ) );
	}
}

<?php
/**
 * Custom Post Types network management — detection and data queries.
 *
 * @package NetworkCentral
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Network_Central_Cpt
 */
class Network_Central_Cpt {

	const OPTION_KEY   = 'network_central_cpt_enabled';
	const NONCE_ACTION = 'network_central_cpt_toggle';
	const PAGE_SLUG    = 'network-central-cpt';

	/**
	 * Post types that are handled by other managers or are internal to WordPress/WooCommerce.
	 */
	const EXCLUDED_TYPES = array(
		'post', 'page', 'attachment', 'revision', 'nav_menu_item',
		'custom_css', 'customize_changeset', 'oembed_cache', 'user_request',
		'wp_block', 'wp_template', 'wp_template_part', 'wp_global_styles',
		'wp_navigation', 'wp_font_family', 'wp_font_face',
		'product', 'shop_order', 'shop_coupon', 'shop_order_refund', 'product_variation',
	);

	// ── Option ────────────────────────────────────────────────────────────

	public static function is_enabled() {
		return (bool) get_site_option( self::OPTION_KEY, false );
	}

	public static function set_enabled( $enabled ) {
		update_site_option( self::OPTION_KEY, (bool) $enabled );
	}

	// ── CPT discovery ─────────────────────────────────────────────────────

	/**
	 * Returns all unique public CPT slugs registered across the network,
	 * keyed by slug with the label as value.
	 *
	 * @param int $filter_site_id
	 * @return array<string,string>
	 */
	public static function get_cpt_types( $filter_site_id = 0 ) {
		$sites = self::_sites( $filter_site_id );
		$types = array();

		foreach ( $sites as $site ) {
			switch_to_blog( $site->blog_id );
			$registered = get_post_types( array( 'public' => true, '_builtin' => false ), 'objects' );
			foreach ( $registered as $slug => $obj ) {
				if ( in_array( $slug, self::EXCLUDED_TYPES, true ) ) {
					continue;
				}
				if ( ! isset( $types[ $slug ] ) ) {
					$types[ $slug ] = $obj->labels->singular_name ?: $slug;
				}
			}
			restore_current_blog();
		}

		ksort( $types );
		return $types;
	}

	// ── Counts ────────────────────────────────────────────────────────────

	public static function count_cpt_posts( $post_type, $filter_site_id = 0 ) {
		global $wpdb;
		$total     = 0;
		$post_type = sanitize_key( $post_type );

		foreach ( self::_sites( $filter_site_id ) as $site ) {
			switch_to_blog( $site->blog_id );
			$registered = get_post_types( array( 'public' => true, '_builtin' => false ) );
			if ( in_array( $post_type, $registered, true ) && ! in_array( $post_type, self::EXCLUDED_TYPES, true ) ) {
				$p      = $wpdb->prefix;
				$total += (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$p}posts WHERE post_type=%s AND post_status IN('publish','draft','pending','private')",
						$post_type
					)
				);
			}
			restore_current_blog();
		}
		return $total;
	}

	// ── Records ───────────────────────────────────────────────────────────

	public static function get_cpt_posts( $post_type, $filter_site_id = 0, $per_page = 25, $paged = 1 ) {
		global $wpdb;
		$results   = array();
		$offset    = ( max( 1, $paged ) - 1 ) * $per_page;
		$post_type = sanitize_key( $post_type );

		foreach ( self::_sites( $filter_site_id ) as $site ) {
			switch_to_blog( $site->blog_id );
			$registered = get_post_types( array( 'public' => true, '_builtin' => false ) );

			if ( ! in_array( $post_type, $registered, true ) || in_array( $post_type, self::EXCLUDED_TYPES, true ) ) {
				restore_current_blog();
				continue;
			}

			$p         = $wpdb->prefix;
			$u         = $wpdb->base_prefix . 'users';
			$site_name = get_bloginfo( 'name' );
			$rows      = $wpdb->get_results( // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->prepare(
					"SELECT p.ID, p.post_title, p.post_status, p.post_date, u.display_name AS author_name
					 FROM {$p}posts p
					 LEFT JOIN {$u} u ON u.ID = p.post_author
					 WHERE p.post_type = %s
					   AND p.post_status IN('publish','draft','pending','private')
					 ORDER BY p.post_date DESC
					 LIMIT %d OFFSET %d",
					$post_type, $per_page, $offset
				)
			);

			foreach ( $rows as $r ) {
				$results[] = array(
					'site_id'   => (int) $site->blog_id,
					'site_name' => $site_name,
					'id'        => (int) $r->ID,
					'title'     => $r->post_title,
					'status'    => $r->post_status,
					'date'      => $r->post_date,
					'author'    => $r->author_name ?: '—',
					'edit_url'  => get_admin_url( $site->blog_id, 'post.php?post=' . $r->ID . '&action=edit' ),
				);
			}
			restore_current_blog();
		}
		return $results;
	}

	// ── Helpers ───────────────────────────────────────────────────────────

	public static function get_all_sites() {
		return get_sites( array( 'number' => 200, 'deleted' => 0, 'archived' => 0, 'spam' => 0 ) );
	}

	private static function _sites( $filter_site_id ) {
		$sites = self::get_all_sites();
		if ( ! $filter_site_id ) {
			return $sites;
		}
		return array_filter(
			$sites,
			function( $s ) use ( $filter_site_id ) {
				return (int) $s->blog_id === (int) $filter_site_id;
			}
		);
	}
}

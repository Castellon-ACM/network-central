<?php
/**
 * WooCommerce network management — detection, option, and product queries.
 *
 * @package NetworkCentral
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Network_Central_Woo
 */
class Network_Central_Woo {

	const OPTION_KEY   = 'network_central_woo_enabled';
	const WC_PLUGIN    = 'woocommerce/woocommerce.php';
	const NONCE_ACTION = 'network_central_woo_toggle';

	/**
	 * Whether network product management is enabled.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return (bool) get_site_option( self::OPTION_KEY, false );
	}

	/**
	 * Enable or disable network product management.
	 *
	 * @param bool $enabled
	 * @return void
	 */
	public static function set_enabled( $enabled ) {
		update_site_option( self::OPTION_KEY, (bool) $enabled );
	}

	/**
	 * Returns site objects where WooCommerce is active.
	 *
	 * @return WP_Site[]
	 */
	public static function get_woo_sites() {
		$sites           = get_sites( array( 'number' => 200, 'deleted' => 0, 'archived' => 0, 'spam' => 0 ) );
		$network_plugins = array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
		$wc_network      = in_array( self::WC_PLUGIN, $network_plugins, true );
		$woo_sites       = array();

		foreach ( $sites as $site ) {
			if ( $wc_network ) {
				$woo_sites[] = $site;
				continue;
			}
			switch_to_blog( $site->blog_id );
			$active = get_option( 'active_plugins', array() );
			if ( in_array( self::WC_PLUGIN, $active, true ) ) {
				$woo_sites[] = $site;
			}
			restore_current_blog();
		}

		return $woo_sites;
	}

	/**
	 * Total product count across all WooCommerce sites (or one site).
	 *
	 * @param int $filter_site_id 0 = all sites.
	 * @return int
	 */
	public static function count_products( $filter_site_id = 0 ) {
		global $wpdb;

		$woo_sites = self::get_woo_sites();
		if ( $filter_site_id ) {
			$woo_sites = array_filter(
				$woo_sites,
				function( $s ) use ( $filter_site_id ) {
					return (int) $s->blog_id === (int) $filter_site_id;
				}
			);
		}

		$total = 0;
		foreach ( $woo_sites as $site ) {
			switch_to_blog( $site->blog_id );
			$prefix = $wpdb->prefix;
			$count  = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT COUNT(*) FROM {$prefix}posts
				 WHERE post_type = 'product'
				   AND post_status IN ('publish','draft','pending','private')"
			);
			$total += $count;
			restore_current_blog();
		}

		return $total;
	}

	/**
	 * Get products from all WooCommerce sites or a specific one.
	 *
	 * @param int $filter_site_id 0 = all sites.
	 * @param int $per_page
	 * @param int $paged
	 * @return array[]
	 */
	public static function get_products( $filter_site_id = 0, $per_page = 25, $paged = 1 ) {
		global $wpdb;

		$woo_sites = self::get_woo_sites();
		if ( $filter_site_id ) {
			$woo_sites = array_filter(
				$woo_sites,
				function( $s ) use ( $filter_site_id ) {
					return (int) $s->blog_id === (int) $filter_site_id;
				}
			);
		}

		$results = array();
		$offset  = ( max( 1, $paged ) - 1 ) * $per_page;

		foreach ( $woo_sites as $site ) {
			switch_to_blog( $site->blog_id );

			$prefix    = $wpdb->prefix;
			$site_name = get_bloginfo( 'name' );

			$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->prepare(
					"SELECT p.ID, p.post_title, p.post_status,
					        MAX( CASE WHEN pm.meta_key = '_price'        THEN pm.meta_value END ) AS price,
					        MAX( CASE WHEN pm.meta_key = '_stock_status' THEN pm.meta_value END ) AS stock_status,
					        MAX( CASE WHEN pm.meta_key = '_sku'          THEN pm.meta_value END ) AS sku
					 FROM {$prefix}posts p
					 LEFT JOIN {$prefix}postmeta pm ON pm.post_id = p.ID
					 WHERE p.post_type   = 'product'
					   AND p.post_status IN ('publish','draft','pending','private')
					 GROUP BY p.ID
					 ORDER BY p.post_date DESC
					 LIMIT %d OFFSET %d",
					$per_page,
					$offset
				)
			);

			foreach ( $rows as $row ) {
				$results[] = array(
					'site_id'   => (int) $site->blog_id,
					'site_name' => $site_name,
					'id'        => (int) $row->ID,
					'title'     => $row->post_title,
					'status'    => $row->post_status,
					'price'     => $row->price,
					'stock'     => $row->stock_status ? $row->stock_status : 'instock',
					'sku'       => $row->sku,
					'edit_url'  => get_admin_url( $site->blog_id, 'post.php?post=' . $row->ID . '&action=edit' ),
				);
			}

			restore_current_blog();
		}

		return $results;
	}
}

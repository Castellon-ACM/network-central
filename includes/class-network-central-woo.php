<?php
/**
 * WooCommerce network management — detection, option, and data queries.
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
	const PAGE_SLUG    = 'network-central-woo';

	// ── Option ────────────────────────────────────────────────────────────

	public static function is_enabled() {
		return (bool) get_site_option( self::OPTION_KEY, false );
	}

	public static function set_enabled( $enabled ) {
		update_site_option( self::OPTION_KEY, (bool) $enabled );
	}

	// ── Site detection ────────────────────────────────────────────────────

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
	 * Check if HPOS (High Performance Order Storage) is active on current blog.
	 * Must be called after switch_to_blog().
	 *
	 * @return bool
	 */
	private static function hpos_enabled() {
		global $wpdb;
		if ( 'yes' !== get_option( 'woocommerce_feature_custom_order_tables_enabled', 'no' ) ) {
			return false;
		}
		$table = $wpdb->prefix . 'wc_orders';
		return (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	// ── Products ──────────────────────────────────────────────────────────

	public static function count_products( $filter_site_id = 0 ) {
		global $wpdb;
		$total = 0;
		foreach ( self::_sites( $filter_site_id ) as $site ) {
			switch_to_blog( $site->blog_id );
			$p     = $wpdb->prefix;
			$total += (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$p}posts WHERE post_type='product' AND post_status IN('publish','draft','pending','private')" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			restore_current_blog();
		}
		return $total;
	}

	public static function get_products( $filter_site_id = 0, $per_page = 25, $paged = 1 ) {
		global $wpdb;
		$results = array();
		$offset  = ( max( 1, $paged ) - 1 ) * $per_page;

		foreach ( self::_sites( $filter_site_id ) as $site ) {
			switch_to_blog( $site->blog_id );
			$p         = $wpdb->prefix;
			$site_name = get_bloginfo( 'name' );
			$rows      = $wpdb->get_results( // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->prepare(
					"SELECT p.ID, p.post_title, p.post_status,
					        MAX(CASE WHEN pm.meta_key='_price'        THEN pm.meta_value END) AS price,
					        MAX(CASE WHEN pm.meta_key='_stock_status' THEN pm.meta_value END) AS stock_status,
					        MAX(CASE WHEN pm.meta_key='_sku'          THEN pm.meta_value END) AS sku
					 FROM {$p}posts p
					 LEFT JOIN {$p}postmeta pm ON pm.post_id=p.ID
					 WHERE p.post_type='product'
					   AND p.post_status IN('publish','draft','pending','private')
					 GROUP BY p.ID ORDER BY p.post_date DESC
					 LIMIT %d OFFSET %d",
					$per_page, $offset
				)
			);
			foreach ( $rows as $r ) {
				$results[] = array(
					'site_id'   => (int) $site->blog_id,
					'site_name' => $site_name,
					'id'        => (int) $r->ID,
					'title'     => $r->post_title,
					'status'    => $r->post_status,
					'price'     => $r->price,
					'stock'     => $r->stock_status ? $r->stock_status : 'instock',
					'sku'       => $r->sku,
					'edit_url'  => get_admin_url( $site->blog_id, 'post.php?post=' . $r->ID . '&action=edit' ),
				);
			}
			restore_current_blog();
		}
		return $results;
	}

	// ── Orders ────────────────────────────────────────────────────────────

	public static function count_orders( $filter_site_id = 0 ) {
		global $wpdb;
		$total = 0;
		foreach ( self::_sites( $filter_site_id ) as $site ) {
			switch_to_blog( $site->blog_id );
			$p = $wpdb->prefix;
			if ( self::hpos_enabled() ) {
				$total += (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$p}wc_orders WHERE type='shop_order'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			} else {
				$total += (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$p}posts WHERE post_type='shop_order' AND post_status NOT IN('trash','auto-draft')" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			}
			restore_current_blog();
		}
		return $total;
	}

	public static function get_orders( $filter_site_id = 0, $per_page = 25, $paged = 1 ) {
		global $wpdb;
		$results = array();
		$offset  = ( max( 1, $paged ) - 1 ) * $per_page;

		foreach ( self::_sites( $filter_site_id ) as $site ) {
			switch_to_blog( $site->blog_id );
			$p         = $wpdb->prefix;
			$site_name = get_bloginfo( 'name' );

			if ( self::hpos_enabled() ) {
				$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$wpdb->prepare(
						"SELECT o.id AS ID, o.status, o.total_amount AS total, o.date_created_gmt AS date,
						        o.billing_email AS email,
						        oa.first_name AS first_name, oa.last_name AS last_name
						 FROM {$p}wc_orders o
						 LEFT JOIN {$p}wc_order_addresses oa ON oa.order_id=o.id AND oa.address_type='billing'
						 WHERE o.type='shop_order'
						 ORDER BY o.date_created_gmt DESC
						 LIMIT %d OFFSET %d",
						$per_page, $offset
					)
				);
			} else {
				$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$wpdb->prepare(
						"SELECT p.ID, p.post_status AS status, p.post_date AS date,
						        MAX(CASE WHEN pm.meta_key='_order_total'        THEN pm.meta_value END) AS total,
						        MAX(CASE WHEN pm.meta_key='_billing_email'      THEN pm.meta_value END) AS email,
						        MAX(CASE WHEN pm.meta_key='_billing_first_name' THEN pm.meta_value END) AS first_name,
						        MAX(CASE WHEN pm.meta_key='_billing_last_name'  THEN pm.meta_value END) AS last_name
						 FROM {$p}posts p
						 LEFT JOIN {$p}postmeta pm ON pm.post_id=p.ID
						 WHERE p.post_type='shop_order'
						   AND p.post_status NOT IN('trash','auto-draft')
						 GROUP BY p.ID ORDER BY p.post_date DESC
						 LIMIT %d OFFSET %d",
						$per_page, $offset
					)
				);
			}

			foreach ( $rows as $r ) {
				$status = isset( $r->status ) ? preg_replace( '/^wc-/', '', $r->status ) : '';
				$results[] = array(
					'site_id'   => (int) $site->blog_id,
					'site_name' => $site_name,
					'id'        => (int) $r->ID,
					'status'    => $status,
					'total'     => isset( $r->total ) ? $r->total : '',
					'email'     => isset( $r->email ) ? $r->email : '',
					'name'      => trim( ( isset( $r->first_name ) ? $r->first_name : '' ) . ' ' . ( isset( $r->last_name ) ? $r->last_name : '' ) ),
					'date'      => isset( $r->date ) ? $r->date : '',
					'edit_url'  => get_admin_url( $site->blog_id, 'post.php?post=' . $r->ID . '&action=edit' ),
				);
			}
			restore_current_blog();
		}
		return $results;
	}

	// ── Customers ─────────────────────────────────────────────────────────

	public static function count_customers( $filter_site_id = 0 ) {
		global $wpdb;
		$total = 0;
		foreach ( self::_sites( $filter_site_id ) as $site ) {
			switch_to_blog( $site->blog_id );
			$cap_key = $wpdb->prefix . 'capabilities';
			$total  += (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->prepare(
					"SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} WHERE meta_key=%s AND meta_value LIKE %s",
					$cap_key, '%customer%'
				)
			);
			restore_current_blog();
		}
		return $total;
	}

	public static function get_customers( $filter_site_id = 0, $per_page = 25, $paged = 1 ) {
		global $wpdb;
		$results = array();
		$offset  = ( max( 1, $paged ) - 1 ) * $per_page;

		foreach ( self::_sites( $filter_site_id ) as $site ) {
			switch_to_blog( $site->blog_id );
			$cap_key   = $wpdb->prefix . 'capabilities';
			$um        = $wpdb->usermeta;
			$u         = $wpdb->base_prefix . 'users';
			$site_name = get_bloginfo( 'name' );

			$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->prepare(
					"SELECT u.ID, u.user_email, u.display_name, u.user_registered,
					        MAX(CASE WHEN um.meta_key='billing_first_name' THEN um.meta_value END) AS first_name,
					        MAX(CASE WHEN um.meta_key='billing_last_name'  THEN um.meta_value END) AS last_name,
					        MAX(CASE WHEN um.meta_key='billing_country'    THEN um.meta_value END) AS country
					 FROM {$u} u
					 INNER JOIN {$um} cap ON cap.user_id=u.ID AND cap.meta_key=%s AND cap.meta_value LIKE %s
					 LEFT JOIN  {$um} um  ON um.user_id=u.ID
					 GROUP BY u.ID ORDER BY u.user_registered DESC
					 LIMIT %d OFFSET %d",
					$cap_key, '%customer%', $per_page, $offset
				)
			);
			foreach ( $rows as $r ) {
				$results[] = array(
					'site_id'   => (int) $site->blog_id,
					'site_name' => $site_name,
					'id'        => (int) $r->ID,
					'email'     => $r->user_email,
					'name'      => trim( $r->first_name . ' ' . $r->last_name ) ?: $r->display_name,
					'country'   => $r->country,
					'registered'=> $r->user_registered,
					'edit_url'  => get_admin_url( $site->blog_id, 'user-edit.php?user_id=' . $r->ID ),
				);
			}
			restore_current_blog();
		}
		return $results;
	}

	// ── Coupons ───────────────────────────────────────────────────────────

	public static function count_coupons( $filter_site_id = 0 ) {
		global $wpdb;
		$total = 0;
		foreach ( self::_sites( $filter_site_id ) as $site ) {
			switch_to_blog( $site->blog_id );
			$p     = $wpdb->prefix;
			$total += (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$p}posts WHERE post_type='shop_coupon' AND post_status='publish'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			restore_current_blog();
		}
		return $total;
	}

	public static function get_coupons( $filter_site_id = 0, $per_page = 25, $paged = 1 ) {
		global $wpdb;
		$results = array();
		$offset  = ( max( 1, $paged ) - 1 ) * $per_page;

		foreach ( self::_sites( $filter_site_id ) as $site ) {
			switch_to_blog( $site->blog_id );
			$p         = $wpdb->prefix;
			$site_name = get_bloginfo( 'name' );
			$rows      = $wpdb->get_results( // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->prepare(
					"SELECT p.ID, p.post_title AS code, p.post_status,
					        MAX(CASE WHEN pm.meta_key='discount_type'  THEN pm.meta_value END) AS type,
					        MAX(CASE WHEN pm.meta_key='coupon_amount'  THEN pm.meta_value END) AS amount,
					        MAX(CASE WHEN pm.meta_key='usage_count'    THEN pm.meta_value END) AS usage_count,
					        MAX(CASE WHEN pm.meta_key='usage_limit'    THEN pm.meta_value END) AS usage_limit,
					        MAX(CASE WHEN pm.meta_key='date_expires'   THEN pm.meta_value END) AS expires
					 FROM {$p}posts p
					 LEFT JOIN {$p}postmeta pm ON pm.post_id=p.ID
					 WHERE p.post_type='shop_coupon' AND p.post_status='publish'
					 GROUP BY p.ID ORDER BY p.post_date DESC
					 LIMIT %d OFFSET %d",
					$per_page, $offset
				)
			);
			foreach ( $rows as $r ) {
				$expires = '';
				if ( ! empty( $r->expires ) ) {
					$expires = date_i18n( get_option( 'date_format' ), (int) $r->expires );
				}
				$results[] = array(
					'site_id'     => (int) $site->blog_id,
					'site_name'   => $site_name,
					'id'          => (int) $r->ID,
					'code'        => strtoupper( $r->code ),
					'type'        => $r->type,
					'amount'      => $r->amount,
					'usage_count' => (int) $r->usage_count,
					'usage_limit' => $r->usage_limit ? (int) $r->usage_limit : '∞',
					'expires'     => $expires,
					'edit_url'    => get_admin_url( $site->blog_id, 'post.php?post=' . $r->ID . '&action=edit' ),
				);
			}
			restore_current_blog();
		}
		return $results;
	}

	// ── Helpers ───────────────────────────────────────────────────────────

	/**
	 * Returns woo sites filtered by site ID if provided.
	 *
	 * @param int $filter_site_id
	 * @return WP_Site[]
	 */
	private static function _sites( $filter_site_id ) {
		$sites = self::get_woo_sites();
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

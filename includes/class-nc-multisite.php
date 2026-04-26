<?php
/**
 * Multisite enable/disable orchestrator.
 *
 * @package NetworkCentral
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class NC_Multisite
 */
class NC_Multisite {

	/**
	 * Enable multisite: write wp-config constants, update .htaccess, create network tables.
	 *
	 * @return bool True on success.
	 */
	public static function enable() {
		$ok = NC_Wpconfig::enable_multisite_full();
		if ( ! $ok ) {
			return false;
		}
		if ( NC_Htaccess::is_writable() ) {
			NC_Htaccess::add_multisite_rules();
		}
		self::install_network_tables();
		return true;
	}

	/**
	 * Disable multisite: remove constants from wp-config and restore single-site .htaccess.
	 *
	 * @return bool True on success.
	 */
	public static function disable() {
		$ok = NC_Wpconfig::disable_multisite_full();
		if ( $ok && NC_Htaccess::is_writable() ) {
			NC_Htaccess::restore_single_site_rules();
		}
		return $ok;
	}

	/**
	 * Create network DB tables (wp_site, wp_blogs, etc.) in the current request.
	 *
	 * @return void
	 */
	private static function install_network_tables() {
		global $wpdb;

		$domain = (string) parse_url( home_url(), PHP_URL_HOST );
		$path   = (string) parse_url( home_url(), PHP_URL_PATH );

		if ( empty( $domain ) ) {
			$domain = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : 'localhost';
		}
		$path = rtrim( $path, '/' );
		if ( '' === $path ) {
			$path = '/';
		}

		$const_values = array(
			'MULTISITE'            => true,
			'SUBDOMAIN_INSTALL'    => false,
			'DOMAIN_CURRENT_SITE'  => $domain,
			'PATH_CURRENT_SITE'    => $path,
			'SITE_ID_CURRENT_SITE' => 1,
			'BLOG_ID_CURRENT_SITE' => 1,
		);
		foreach ( $const_values as $const => $value ) {
			if ( ! defined( $const ) ) {
				define( $const, $value );
			}
		}

		foreach ( $wpdb->tables( 'ms_global' ) as $table => $prefixed_table ) {
			$wpdb->$table = $prefixed_table;
		}

		require_once ABSPATH . 'wp-admin/includes/network.php';
		if ( network_domain_check() ) {
			return;
		}
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		install_network();
		populate_network( 1, $domain, get_option( 'admin_email' ), get_option( 'blogname' ), $path, false );
	}
}

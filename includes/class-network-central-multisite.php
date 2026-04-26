<?php
/**
 * Multisite enable/disable orchestrator.
 *
 * @package NetworkCentral
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Network_Central_Multisite
 */
class Network_Central_Multisite {

	/**
	 * Enable multisite: write wp-config constants, update .htaccess, create network tables.
	 *
	 * @return bool True on success.
	 */
	public static function enable() {
		$ok = Network_Central_Wpconfig::enable_multisite_full();
		if ( $ok && Network_Central_Htaccess::is_writable() ) {
			Network_Central_Htaccess::add_multisite_rules();
		}
		if ( ! $ok ) {
			return false;
		}
		self::run_network_install();
		return true;
	}

	/**
	 * Disable multisite: remove constants from wp-config and restore single-site .htaccess.
	 *
	 * @return bool True on success.
	 */
	public static function disable() {
		$ok = Network_Central_Wpconfig::disable_multisite_full();
		if ( $ok && Network_Central_Htaccess::is_writable() ) {
			Network_Central_Htaccess::restore_single_site_rules();
		}
		return $ok;
	}

	/**
	 * Create network DB tables (wp_site, wp_blogs, etc.) in the current request.
	 * Called right after writing wp-config so tables exist before the redirect.
	 *
	 * @return void
	 */
	public static function run_network_install() {
		global $wpdb;

		$domain = parse_url( home_url(), PHP_URL_HOST );
		$path   = parse_url( home_url(), PHP_URL_PATH );
		if ( empty( $domain ) ) {
			$domain = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : 'localhost';
		}
		if ( empty( $path ) ) {
			$path = '/';
		}
		$path = rtrim( $path, '/' );
		if ( '' === $path ) {
			$path = '/';
		}

		if ( ! defined( 'MULTISITE' ) ) {
			define( 'MULTISITE', true );
		}
		if ( ! defined( 'SUBDOMAIN_INSTALL' ) ) {
			define( 'SUBDOMAIN_INSTALL', false );
		}
		if ( ! defined( 'DOMAIN_CURRENT_SITE' ) ) {
			define( 'DOMAIN_CURRENT_SITE', $domain );
		}
		if ( ! defined( 'PATH_CURRENT_SITE' ) ) {
			define( 'PATH_CURRENT_SITE', $path );
		}
		if ( ! defined( 'SITE_ID_CURRENT_SITE' ) ) {
			define( 'SITE_ID_CURRENT_SITE', 1 );
		}
		if ( ! defined( 'BLOG_ID_CURRENT_SITE' ) ) {
			define( 'BLOG_ID_CURRENT_SITE', 1 );
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
		$email = get_option( 'admin_email' );
		$name  = get_option( 'blogname' );
		populate_network( 1, $domain, $email, $name, $path, false );
	}
}

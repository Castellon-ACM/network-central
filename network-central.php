<?php
/**
 * Plugin Name: Network Central
 * Description: Enable or disable WordPress Multisite with a single toggle.
 * Version: 1.0.1
 * Author: 	Alejandro Castellón <Castellon-ACM>
 * Author URI: https://github.com/Castellon-ACM
 * Text Domain: network-central
 * Domain Path: /languages
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package NetworkCentral
 * @author  Alejandro Castellón <Castellon-ACM>
 * @copyright 2026 Alejandro Castellón
 * @license GPL-2.0+
 *
 * @wordpress-plugin
 *
 * Prefix: network_central_
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

define( 'NETWORK_CENTRAL_VERSION',      '1.0.1' );
define( 'NETWORK_CENTRAL_FILE',         __FILE__ );
define( 'NETWORK_CENTRAL_PLUGIN_URL',   plugin_dir_url( __FILE__ ) );
define( 'NETWORK_CENTRAL_PLUGIN_PATH',  plugin_dir_path( __FILE__ ) );
define( 'NETWORK_CENTRAL_PAGE_SLUG',    'network-central' );
define( 'NETWORK_CENTRAL_NONCE_ACTION', 'network_central_toggle_multisite' );

require_once NETWORK_CENTRAL_PLUGIN_PATH . 'includes/class-network-central-wpconfig.php';
require_once NETWORK_CENTRAL_PLUGIN_PATH . 'includes/class-network-central-htaccess.php';
require_once NETWORK_CENTRAL_PLUGIN_PATH . 'includes/class-network-central-multisite.php';
require_once NETWORK_CENTRAL_PLUGIN_PATH . 'includes/class-network-central-page.php';

add_action( 'plugins_loaded', 'network_central_plugin_init' );
add_action( 'admin_init',     'network_central_maybe_handle_toggle', 1 );

if ( is_multisite() ) {
	// Multisite active: register only in the Network Admin.
	add_action( 'network_admin_menu',            'network_central_add_menu_page' );
	add_action( 'network_admin_enqueue_scripts', 'network_central_enqueue_assets' );
} else {
	// Single site: register in the regular admin so the toggle is reachable.
	add_action( 'admin_menu',            'network_central_add_menu_page' );
	add_action( 'admin_enqueue_scripts', 'network_central_enqueue_assets' );
}

/**
 * Load plugin text domain.
 *
 * @return void
 */
function network_central_plugin_init() {
	load_plugin_textdomain( 'network-central', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

/**
 * Register top-level admin menu page.
 *
 * @return void
 */
function network_central_add_menu_page() {
	$capability = is_multisite() ? 'manage_network' : 'manage_options';
	add_menu_page(
		__( 'Network Central', 'network-central' ),
		__( 'Network Central', 'network-central' ),
		$capability,
		NETWORK_CENTRAL_PAGE_SLUG,
		array( 'Network_Central_Page', 'render' ),
		'dashicons-networking',
		79
	);
}

/**
 * Enqueue Tailwind and font on the plugin page only.
 *
 * @param string $hook_suffix Current admin page hook.
 * @return void
 */
function network_central_enqueue_assets( $hook_suffix ) {
	if ( 'toplevel_page_network-central' !== $hook_suffix ) {
		return;
	}
	wp_enqueue_script( 'network-central-tailwind', 'https://cdn.tailwindcss.com', array(), null, false );
	wp_add_inline_script(
		'network-central-tailwind',
		'tailwind.config = { darkMode: "class", theme: { extend: { fontFamily: { mono: ["JetBrains Mono", "Consolas", "monospace"] } } } }',
		'after'
	);
	wp_enqueue_style(
		'network-central-font',
		'https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&display=swap',
		array(),
		null
	);
}

/**
 * Return the correct base admin URL depending on context (network vs single-site).
 *
 * @return string
 */
function network_central_admin_url() {
	return is_multisite() ? network_admin_url( 'admin.php' ) : admin_url( 'admin.php' );
}

/**
 * Handle the Multisite toggle form submission.
 *
 * @return void
 */
function network_central_maybe_handle_toggle() {
	if ( ! isset( $_POST['network_central_nonce'] ) || empty( $_POST['network_central_nonce'] ) ) {
		return;
	}
	if ( ! isset( $_GET['page'] ) || NETWORK_CENTRAL_PAGE_SLUG !== $_GET['page'] ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['network_central_nonce'] ) ), NETWORK_CENTRAL_NONCE_ACTION ) ) {
		wp_die( esc_html__( 'Security check failed.', 'network-central' ) );
	}

	$capability = is_multisite() ? 'manage_network' : 'manage_options';
	if ( ! current_user_can( $capability ) ) {
		wp_die( esc_html__( 'Permission denied.', 'network-central' ) );
	}

	$base_url = network_central_admin_url();
	$enable   = isset( $_POST['network_central_multisite'] ) && '1' === $_POST['network_central_multisite'];

	if ( ! Network_Central_Wpconfig::is_writable() ) {
		wp_safe_redirect( add_query_arg( array( 'page' => NETWORK_CENTRAL_PAGE_SLUG, 'nc_err' => 'not_writable' ), $base_url ) );
		exit;
	}

	if ( $enable ) {
		$ok = Network_Central_Multisite::enable();
		if ( $ok ) {
			wp_safe_redirect( add_query_arg( array( 'page' => NETWORK_CENTRAL_PAGE_SLUG, 'nc_ok' => 'enabled' ), admin_url( 'admin.php' ) ) );
			exit;
		}
		wp_safe_redirect( add_query_arg( array( 'page' => NETWORK_CENTRAL_PAGE_SLUG, 'nc_err' => 'write_failed' ), $base_url ) );
		exit;
	}

	$was_active = ( defined( 'MULTISITE' ) && MULTISITE ) || Network_Central_Wpconfig::get_multisite_allowed();
	if ( $was_active ) {
		$ok = Network_Central_Multisite::disable();
		if ( $ok ) {
			wp_safe_redirect( add_query_arg( array( 'page' => NETWORK_CENTRAL_PAGE_SLUG, 'nc_ok' => 'disabled' ), admin_url( 'admin.php' ) ) );
			exit;
		}
		wp_safe_redirect( add_query_arg( array( 'page' => NETWORK_CENTRAL_PAGE_SLUG, 'nc_err' => 'write_failed' ), $base_url ) );
		exit;
	}

	wp_safe_redirect( add_query_arg( array( 'page' => NETWORK_CENTRAL_PAGE_SLUG ), $base_url ) );
	exit;
}

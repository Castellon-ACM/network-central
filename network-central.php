<?php
/**
 * Plugin Name: Network Central
 * Description: Activa o desactiva WordPress Multisite con un solo toggle.
 * Version: 1.0.0
 * Author: 	Alejandro Castellón <Castellon-ACM>
 * Text Domain: network-central
 * License: GPL-2.0+
 *
 * @package NetworkCentral
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

define( 'NC_VERSION', '1.0.0' );
define( 'NC_FILE', __FILE__ );
define( 'NC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'NC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'NC_PAGE_SLUG', 'network-central' );
define( 'NC_NONCE_ACTION', 'nc_toggle_multisite' );

require_once NC_PLUGIN_PATH . 'includes/class-nc-wpconfig.php';
require_once NC_PLUGIN_PATH . 'includes/class-nc-htaccess.php';
require_once NC_PLUGIN_PATH . 'includes/class-nc-multisite.php';
require_once NC_PLUGIN_PATH . 'includes/class-nc-page.php';

add_action( 'admin_menu', 'nc_add_menu_page' );
add_action( 'admin_enqueue_scripts', 'nc_enqueue_assets' );
add_action( 'admin_init', 'nc_maybe_handle_toggle', 1 );

/**
 * Register admin menu page.
 *
 * @return void
 */
function nc_add_menu_page() {
	add_menu_page(
		__( 'Network Central', 'network-central' ),
		__( 'Network Central', 'network-central' ),
		'manage_options',
		NC_PAGE_SLUG,
		array( 'NC_Page', 'render' ),
		'dashicons-networking',
		79
	);
}

/**
 * Enqueue Tailwind and styles on our page only.
 *
 * @param string $hook_suffix Current admin page hook.
 * @return void
 */
function nc_enqueue_assets( $hook_suffix ) {
	if ( 'toplevel_page_network-central' !== $hook_suffix ) {
		return;
	}
	wp_enqueue_script( 'nc-tailwind', 'https://cdn.tailwindcss.com', array(), null, false );
	wp_add_inline_script(
		'nc-tailwind',
		'tailwind.config = { darkMode: "class", theme: { extend: { fontFamily: { mono: ["JetBrains Mono", "Consolas", "monospace"] } } } }',
		'after'
	);
	wp_enqueue_style(
		'nc-font-mono',
		'https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&display=swap',
		array(),
		null
	);
}

/**
 * Handle toggle form submit.
 *
 * @return void
 */
function nc_maybe_handle_toggle() {
	if ( ! isset( $_POST['nc_multisite_nonce'] ) || empty( $_POST['nc_multisite_nonce'] ) ) {
		return;
	}
	if ( ! isset( $_GET['page'] ) || NC_PAGE_SLUG !== $_GET['page'] ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nc_multisite_nonce'] ) ), NC_NONCE_ACTION ) ) {
		wp_die( esc_html__( 'Security check failed.', 'network-central' ) );
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Permission denied.', 'network-central' ) );
	}

	$enable = isset( $_POST['nc_multisite'] ) && '1' === $_POST['nc_multisite'];

	if ( ! NC_Wpconfig::is_writable() ) {
		wp_safe_redirect( add_query_arg( array( 'page' => NC_PAGE_SLUG, 'nc_err' => 'not_writable' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	if ( $enable ) {
		$ok = NC_Multisite::enable();
		if ( $ok ) {
			wp_safe_redirect( admin_url( 'network.php' ) );
			exit;
		}
		wp_safe_redirect( add_query_arg( array( 'page' => NC_PAGE_SLUG, 'nc_err' => 'write_failed' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	$was_active = ( defined( 'MULTISITE' ) && MULTISITE ) || NC_Wpconfig::get_multisite_allowed();
	if ( $was_active ) {
		$ok = NC_Multisite::disable();
		if ( $ok ) {
			wp_safe_redirect( add_query_arg( array( 'page' => NC_PAGE_SLUG, 'nc_ok' => 'disabled' ), admin_url( 'admin.php' ) ) );
			exit;
		}
		wp_safe_redirect( add_query_arg( array( 'page' => NC_PAGE_SLUG, 'nc_err' => 'write_failed' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	wp_safe_redirect( add_query_arg( array( 'page' => NC_PAGE_SLUG ), admin_url( 'admin.php' ) ) );
	exit;
}

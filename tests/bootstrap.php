<?php
/**
 * PHPUnit bootstrap — defines WordPress stubs so plugin files load outside WP.
 *
 * IMPORTANT: vendor/autoload.php (which initialises Patchwork) must be loaded
 * BEFORE any stub functions are defined, so Brain Monkey can intercept them.
 */

// ── Autoloader first — Patchwork must be active before function stubs ─────
require_once __DIR__ . '/../vendor/autoload.php';

// ── WordPress constants ────────────────────────────────────────────────────
define( 'ABSPATH', sys_get_temp_dir() . '/nc-test-abspath/' );

if ( ! is_dir( ABSPATH ) ) {
	mkdir( ABSPATH, 0755, true );
}

// ── WordPress function stubs (always-same behaviour, no mocking needed) ───
if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( string $str ): string {
		return trim( strip_tags( $str ) );
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		return is_array( $value )
			? array_map( 'stripslashes', $value )
			: stripslashes( (string) $value );
	}
}

// ── Plugin files ──────────────────────────────────────────────────────────
require_once __DIR__ . '/../includes/class-network-central-wpconfig.php';
require_once __DIR__ . '/../includes/class-network-central-htaccess.php';
require_once __DIR__ . '/../includes/class-network-central-woo.php';

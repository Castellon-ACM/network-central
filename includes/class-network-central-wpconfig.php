<?php
/**
 * wp-config.php multisite constants writer/remover.
 *
 * @package NetworkCentral
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Network_Central_Wpconfig
 */
class Network_Central_Wpconfig {

	/**
	 * Path to wp-config.php.
	 *
	 * @return string
	 */
	public static function get_path() {
		return ABSPATH . 'wp-config.php';
	}

	/**
	 * Read wp-config.php content.
	 *
	 * @return string
	 */
	public static function get_content() {
		$path    = self::get_path();
		$content = file_exists( $path ) ? file_get_contents( $path ) : '';
		return false !== $content ? $content : '';
	}

	/**
	 * Check if wp-config.php is writable.
	 *
	 * @return bool
	 */
	public static function is_writable() {
		$path = self::get_path();
		return file_exists( $path ) && is_writable( $path );
	}

	/**
	 * Check if WP_ALLOW_MULTISITE is already set to true in wp-config.php.
	 *
	 * @return bool
	 */
	public static function get_multisite_allowed() {
		return (bool) preg_match( '/define\s*\(\s*[\'" ]WP_ALLOW_MULTISITE[\'" ]\s*,\s*true\s*\)/i', self::get_content() );
	}

	/**
	 * Validate PHP syntax of a string (no null bytes, balanced braces).
	 *
	 * @param string $content Content to validate.
	 * @return bool
	 */
	private static function validate_syntax( $content ) {
		if ( false !== strpos( $content, "\0" ) ) {
			return false;
		}
		if ( function_exists( 'exec' ) ) {
			$tmp = wp_tempnam( 'nc-wpconfig-' );
			if ( $tmp ) {
				$written = file_put_contents( $tmp, $content, LOCK_EX );
				if ( false !== $written ) {
					$out = array();
					$ret = -1;
					@exec( 'php -l ' . escapeshellarg( $tmp ) . ' 2>&1', $out, $ret ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					@unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					if ( 0 === $ret ) {
						return true;
					}
				} else {
					@unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				}
			}
		}
		return false === strpos( $content, '<?php' ) ? false : true;
	}

	/**
	 * Write all required multisite constants to wp-config.php (subdirectory install).
	 *
	 * @return bool True on success.
	 */
	public static function enable_multisite_full() {
		if ( ! self::is_writable() ) {
			return false;
		}
		$content = self::get_content();
		$domain  = parse_url( home_url(), PHP_URL_HOST );
		$path    = parse_url( home_url(), PHP_URL_PATH );
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

		$block = "\n" . "define( 'WP_ALLOW_MULTISITE', true );" . "\n"
			. "define( 'MULTISITE', true );" . "\n"
			. "define( 'SUBDOMAIN_INSTALL', false );" . "\n"
			. "define( 'DOMAIN_CURRENT_SITE', '" . str_replace( "'", "\\'", $domain ) . "' );" . "\n"
			. "define( 'PATH_CURRENT_SITE', '" . str_replace( "'", "\\'", $path ) . "' );" . "\n"
			. "define( 'SITE_ID_CURRENT_SITE', 1 );" . "\n"
			. "define( 'BLOG_ID_CURRENT_SITE', 1 );" . "\n";

		$constants = array( 'WP_ALLOW_MULTISITE', 'MULTISITE', 'SUBDOMAIN_INSTALL', 'DOMAIN_CURRENT_SITE', 'PATH_CURRENT_SITE', 'SITE_ID_CURRENT_SITE', 'BLOG_ID_CURRENT_SITE' );
		foreach ( $constants as $const ) {
			$content = preg_replace( '/define\s*\(\s*[\'" ]' . preg_quote( $const, '/' ) . '[\'" ]\s*,\s*[^;]+;\s*\n?/i', '', $content );
		}

		$stop = "/* That's all, stop editing!";
		$pos  = strpos( $content, $stop );
		if ( false !== $pos ) {
			$content = substr_replace( $content, $block . "\n" . $stop, $pos, strlen( $stop ) );
		} else {
			$content = $content . $block;
		}

		if ( ! self::validate_syntax( $content ) ) {
			return false;
		}
		$result = file_put_contents( self::get_path(), $content, LOCK_EX );
		if ( false !== $result && function_exists( 'opcache_invalidate' ) ) {
			opcache_invalidate( self::get_path(), true );
		}
		return false !== $result;
	}

	/**
	 * Remove all multisite constants from wp-config.php to revert to single-site.
	 *
	 * @return bool True on success.
	 */
	public static function disable_multisite_full() {
		if ( ! self::is_writable() ) {
			return false;
		}
		$content   = self::get_content();
		$constants = array(
			'WP_ALLOW_MULTISITE',
			'MULTISITE',
			'SUBDOMAIN_INSTALL',
			'DOMAIN_CURRENT_SITE',
			'PATH_CURRENT_SITE',
			'SITE_ID_CURRENT_SITE',
			'BLOG_ID_CURRENT_SITE',
		);
		foreach ( $constants as $const ) {
			$content = preg_replace( '/\s*define\s*\(\s*[\'" ]' . preg_quote( $const, '/' ) . '[\'" ]\s*,\s*[^;]+;\s*\n?/i', "\n", $content );
		}
		$content = preg_replace( '/\n{3,}/', "\n\n", $content );

		if ( ! self::validate_syntax( $content ) ) {
			return false;
		}
		$result = file_put_contents( self::get_path(), $content, LOCK_EX );
		if ( false !== $result && function_exists( 'opcache_invalidate' ) ) {
			opcache_invalidate( self::get_path(), true );
		}
		return false !== $result;
	}
}

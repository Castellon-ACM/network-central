<?php
/**
 * .htaccess multisite rules writer/remover.
 *
 * @package NetworkCentral
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class NC_Htaccess
 */
class NC_Htaccess {

	/**
	 * Path to .htaccess.
	 *
	 * @return string
	 */
	public static function get_path() {
		return ABSPATH . '.htaccess';
	}

	/**
	 * Read .htaccess content.
	 *
	 * @return string
	 */
	public static function get_content() {
		$path    = self::get_path();
		$content = file_exists( $path ) ? file_get_contents( $path ) : '';
		return false !== $content ? $content : '';
	}

	/**
	 * Check if .htaccess is writable.
	 *
	 * @return bool
	 */
	public static function is_writable() {
		$path = self::get_path();
		return file_exists( $path ) ? is_writable( $path ) : is_writable( ABSPATH );
	}

	/**
	 * Replace WordPress single-site rewrite block with multisite rules.
	 *
	 * @return bool True on success.
	 */
	public static function add_multisite_rules() {
		if ( ! self::is_writable() ) {
			return false;
		}
		$content = self::get_content();

		if ( preg_match( '/# BEGIN Multisite\b/', $content ) ) {
			return true;
		}

		$block = "# BEGIN Multisite\n"
			. "<IfModule mod_rewrite.c>\n"
			. "RewriteEngine On\n"
			. "RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]\n"
			. "RewriteBase /\n"
			. "RewriteRule ^index\\.php$ - [L]\n"
			. "RewriteRule ^([_0-9a-zA-Z-]+/)?wp-admin$ \$1wp-admin/ [R=301,L]\n"
			. "RewriteCond %{REQUEST_FILENAME} -f [OR]\n"
			. "RewriteCond %{REQUEST_FILENAME} -d\n"
			. "RewriteRule ^ - [L]\n"
			. "RewriteRule ^([_0-9a-zA-Z-]+/)?(wp-(content|admin|includes).*) \$2 [L]\n"
			. "RewriteRule ^([_0-9a-zA-Z-]+/)?(\\.php)\$ \$2 [L]\n"
			. "RewriteRule . index.php [L]\n"
			. "</IfModule>\n"
			. "# END Multisite";

		if ( preg_match( '/# BEGIN WordPress\b.*?# END WordPress\b/s', $content ) ) {
			$content = preg_replace( '/# BEGIN WordPress\b.*?# END WordPress\b/s', $block, $content );
		} else {
			$content .= "\n" . $block . "\n";
		}

		return false !== file_put_contents( self::get_path(), $content, LOCK_EX );
	}

	/**
	 * Restore single-site WordPress rewrite rules (remove multisite block).
	 *
	 * @return bool True on success.
	 */
	public static function restore_single_site_rules() {
		if ( ! self::is_writable() ) {
			return false;
		}
		$content = self::get_content();

		$single_block = "# BEGIN WordPress\n"
			. "<IfModule mod_rewrite.c>\n"
			. "RewriteEngine On\n"
			. "RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]\n"
			. "RewriteBase /\n"
			. "RewriteRule ^index\\.php$ - [L]\n"
			. "RewriteCond %{REQUEST_FILENAME} !-f\n"
			. "RewriteCond %{REQUEST_FILENAME} !-d\n"
			. "RewriteRule . /index.php [L]\n"
			. "</IfModule>\n"
			. "# END WordPress";

		if ( preg_match( '/# BEGIN Multisite\b.*?# END Multisite\b/s', $content ) ) {
			$content = preg_replace( '/# BEGIN Multisite\b.*?# END Multisite\b/s', $single_block, $content );
		} else {
			$content .= "\n" . $single_block . "\n";
		}

		return false !== file_put_contents( self::get_path(), $content, LOCK_EX );
	}
}

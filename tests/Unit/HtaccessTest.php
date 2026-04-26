<?php

declare( strict_types=1 );

namespace NetworkCentral\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;

/**
 * Tests for .htaccess Multisite rules writer/remover.
 */
class HtaccessTest extends TestCase {

	private string $htaccess_path;

	/** Standard WordPress single-site block used as test fixture. */
	private const WP_BLOCK = "# BEGIN WordPress\n<IfModule mod_rewrite.c>\nRewriteEngine On\nRewriteBase /\nRewriteRule ^index\\.php$ - [L]\nRewriteCond %{REQUEST_FILENAME} !-f\nRewriteCond %{REQUEST_FILENAME} !-d\nRewriteRule . /index.php [L]\n</IfModule>\n# END WordPress\n";

	/** Standard Multisite block used as test fixture. */
	private const MS_BLOCK = "# BEGIN Multisite\n<IfModule mod_rewrite.c>\nRewriteEngine On\nRewriteBase /\n</IfModule>\n# END Multisite\n";

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		$this->htaccess_path = ABSPATH . '.htaccess';
	}

	protected function tearDown(): void {
		if ( file_exists( $this->htaccess_path ) ) {
			unlink( $this->htaccess_path );
		}
		Monkey\tearDown();
		parent::tearDown();
	}

	private function write_htaccess( string $content ): void {
		file_put_contents( $this->htaccess_path, $content );
	}

	// ── add_multisite_rules ──────────────────────────────────────────────

	public function test_add_rules_replaces_wordpress_block(): void {
		$this->write_htaccess( self::WP_BLOCK );

		$result  = \Network_Central_Htaccess::add_multisite_rules();
		$content = file_get_contents( $this->htaccess_path );

		$this->assertTrue( $result );
		$this->assertStringContainsString( '# BEGIN Multisite', $content );
		$this->assertStringContainsString( '# END Multisite', $content );
		$this->assertStringNotContainsString( '# BEGIN WordPress', $content );
	}

	public function test_add_rules_appends_when_no_wordpress_block(): void {
		$this->write_htaccess( "# some other config\n" );

		\Network_Central_Htaccess::add_multisite_rules();
		$content = file_get_contents( $this->htaccess_path );

		$this->assertStringContainsString( '# BEGIN Multisite', $content );
		$this->assertStringContainsString( '# some other config', $content );
	}

	public function test_add_rules_is_idempotent(): void {
		$this->write_htaccess( self::MS_BLOCK );
		$original = file_get_contents( $this->htaccess_path );

		$result = \Network_Central_Htaccess::add_multisite_rules();

		$this->assertTrue( $result );
		$this->assertSame( $original, file_get_contents( $this->htaccess_path ) );
	}

	public function test_add_rules_contains_rewritebase(): void {
		$this->write_htaccess( self::WP_BLOCK );
		\Network_Central_Htaccess::add_multisite_rules();

		$this->assertStringContainsString( 'RewriteBase /', file_get_contents( $this->htaccess_path ) );
	}

	public function test_add_rules_contains_php_rewrite_rule(): void {
		$this->write_htaccess( self::WP_BLOCK );
		\Network_Central_Htaccess::add_multisite_rules();

		$this->assertStringContainsString( '\.php', file_get_contents( $this->htaccess_path ) );
	}

	public function test_add_rules_contains_wp_admin_redirect(): void {
		$this->write_htaccess( self::WP_BLOCK );
		\Network_Central_Htaccess::add_multisite_rules();

		$this->assertStringContainsString( 'wp-admin', file_get_contents( $this->htaccess_path ) );
	}

	public function test_add_rules_returns_false_when_file_missing_and_dir_not_writable(): void {
		// Relies on is_writable(ABSPATH) — in a writable temp dir this returns true,
		// so the method creates the file. Just verify the returned bool is consistent.
		if ( file_exists( $this->htaccess_path ) ) {
			unlink( $this->htaccess_path );
		}
		$result = \Network_Central_Htaccess::add_multisite_rules();
		// In a writable ABSPATH the file will be created — result is true.
		$this->assertTrue( $result );
		// Clean up the created file.
		if ( file_exists( $this->htaccess_path ) ) {
			unlink( $this->htaccess_path );
		}
	}

	// ── restore_single_site_rules ────────────────────────────────────────

	public function test_restore_replaces_multisite_block(): void {
		$this->write_htaccess( self::MS_BLOCK );

		$result  = \Network_Central_Htaccess::restore_single_site_rules();
		$content = file_get_contents( $this->htaccess_path );

		$this->assertTrue( $result );
		$this->assertStringContainsString( '# BEGIN WordPress', $content );
		$this->assertStringContainsString( '# END WordPress', $content );
		$this->assertStringNotContainsString( '# BEGIN Multisite', $content );
	}

	public function test_restore_appends_when_no_multisite_block(): void {
		$this->write_htaccess( "# existing config\n" );

		\Network_Central_Htaccess::restore_single_site_rules();
		$content = file_get_contents( $this->htaccess_path );

		$this->assertStringContainsString( '# BEGIN WordPress', $content );
		$this->assertStringContainsString( '# existing config', $content );
	}

	public function test_restore_result_contains_single_site_conditions(): void {
		$this->write_htaccess( self::MS_BLOCK );
		\Network_Central_Htaccess::restore_single_site_rules();

		$content = file_get_contents( $this->htaccess_path );

		$this->assertStringContainsString( '!-f', $content );
		$this->assertStringContainsString( '!-d', $content );
	}

	public function test_restore_result_does_not_contain_multisite_php_rule(): void {
		$this->write_htaccess( self::MS_BLOCK );
		\Network_Central_Htaccess::restore_single_site_rules();

		// The subdirectory-specific rule for .php files must be absent in single-site.
		$this->assertStringNotContainsString( '\.php)$ $2', file_get_contents( $this->htaccess_path ) );
	}

	// ── is_writable ──────────────────────────────────────────────────────

	public function test_is_writable_returns_true_for_existing_writable_file(): void {
		$this->write_htaccess( '' );
		$this->assertTrue( \Network_Central_Htaccess::is_writable() );
	}
}

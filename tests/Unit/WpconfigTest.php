<?php

declare( strict_types=1 );

namespace NetworkCentral\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Tests for wp-config.php multisite constants detection and file manipulation.
 */
class WpconfigTest extends TestCase {

	private string $config_path;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		$this->config_path = ABSPATH . 'wp-config.php';
		Functions\when( 'home_url' )->justReturn( 'http://example.com' );
		Functions\when( 'wp_tempnam' )->justReturn( false );
	}

	protected function tearDown(): void {
		if ( file_exists( $this->config_path ) ) {
			unlink( $this->config_path );
		}
		Monkey\tearDown();
		parent::tearDown();
	}

	private function write_config( string $content ): void {
		file_put_contents( $this->config_path, $content );
	}

	// ── get_multisite_allowed ────────────────────────────────────────────

	public function test_get_multisite_allowed_returns_true_when_define_present(): void {
		$this->write_config( "<?php\ndefine('WP_ALLOW_MULTISITE', true);\n" );
		$this->assertTrue( \Network_Central_Wpconfig::get_multisite_allowed() );
	}

	public function test_get_multisite_allowed_returns_false_when_absent(): void {
		$this->write_config( "<?php\n// no multisite\n" );
		$this->assertFalse( \Network_Central_Wpconfig::get_multisite_allowed() );
	}

	public function test_get_multisite_allowed_handles_double_quotes(): void {
		$this->write_config( "<?php\ndefine(\"WP_ALLOW_MULTISITE\", true);\n" );
		$this->assertTrue( \Network_Central_Wpconfig::get_multisite_allowed() );
	}

	public function test_get_multisite_allowed_handles_spaces_around_args(): void {
		$this->write_config( "<?php\ndefine( 'WP_ALLOW_MULTISITE', true );\n" );
		$this->assertTrue( \Network_Central_Wpconfig::get_multisite_allowed() );
	}

	public function test_get_multisite_allowed_returns_false_when_set_to_false(): void {
		$this->write_config( "<?php\ndefine('WP_ALLOW_MULTISITE', false);\n" );
		$this->assertFalse( \Network_Central_Wpconfig::get_multisite_allowed() );
	}

	// ── enable_multisite_full ────────────────────────────────────────────

	public function test_enable_multisite_inserts_before_stop_editing_comment(): void {
		$this->write_config( "<?php\n// settings\n/* That's all, stop editing! Happy publishing. */\nrequire_once ABSPATH . 'wp-settings.php';\n" );

		$result = \Network_Central_Wpconfig::enable_multisite_full();
		$this->assertTrue( $result );

		$content = file_get_contents( $this->config_path );
		$this->assertStringContainsString( "define( 'MULTISITE', true );", $content );
		// Constants must appear before the stop-editing comment.
		$this->assertLessThan(
			strpos( $content, "/* That's all" ),
			strpos( $content, 'MULTISITE' )
		);
	}

	public function test_enable_multisite_fallback_inserts_before_require_once(): void {
		$this->write_config( "<?php\n// no stop-editing comment here\nrequire_once ABSPATH . 'wp-settings.php';\n" );

		$result = \Network_Central_Wpconfig::enable_multisite_full();
		$this->assertTrue( $result );

		$content = file_get_contents( $this->config_path );
		$this->assertStringContainsString( "define( 'MULTISITE', true );", $content );
		// Constants must appear before require_once.
		$this->assertLessThan(
			strpos( $content, 'require_once ABSPATH' ),
			strpos( $content, 'MULTISITE' )
		);
	}

	public function test_enable_multisite_writes_all_seven_constants(): void {
		$this->write_config( "<?php\n/* That's all, stop editing! */\nrequire_once ABSPATH . 'wp-settings.php';\n" );

		\Network_Central_Wpconfig::enable_multisite_full();
		$content = file_get_contents( $this->config_path );

		$expected = array(
			'WP_ALLOW_MULTISITE',
			'MULTISITE',
			'SUBDOMAIN_INSTALL',
			'DOMAIN_CURRENT_SITE',
			'PATH_CURRENT_SITE',
			'SITE_ID_CURRENT_SITE',
			'BLOG_ID_CURRENT_SITE',
		);
		foreach ( $expected as $const ) {
			$this->assertStringContainsString( $const, $content, "Missing constant: {$const}" );
		}
	}

	public function test_enable_multisite_subdomain_install_is_false(): void {
		$this->write_config( "<?php\n/* That's all, stop editing! */\nrequire_once ABSPATH . 'wp-settings.php';\n" );

		\Network_Central_Wpconfig::enable_multisite_full();
		$content = file_get_contents( $this->config_path );

		$this->assertStringContainsString( "define( 'SUBDOMAIN_INSTALL', false );", $content );
	}

	public function test_enable_multisite_uses_domain_from_home_url(): void {
		$this->write_config( "<?php\n/* That's all, stop editing! */\nrequire_once ABSPATH . 'wp-settings.php';\n" );

		\Network_Central_Wpconfig::enable_multisite_full();
		$content = file_get_contents( $this->config_path );

		$this->assertStringContainsString( "define( 'DOMAIN_CURRENT_SITE', 'example.com' );", $content );
	}

	public function test_enable_multisite_deduplicates_existing_constants(): void {
		$this->write_config( "<?php\ndefine('MULTISITE', false);\n/* That's all, stop editing! */\nrequire_once ABSPATH . 'wp-settings.php';\n" );

		\Network_Central_Wpconfig::enable_multisite_full();
		$content = file_get_contents( $this->config_path );

		// Each constant must appear exactly once.
		$this->assertSame( 1, substr_count( $content, "define( 'MULTISITE'" ) );
	}

	public function test_enable_multisite_returns_false_when_file_missing(): void {
		// No wp-config.php created — is_writable() must return false.
		if ( file_exists( $this->config_path ) ) {
			unlink( $this->config_path );
		}
		$this->assertFalse( \Network_Central_Wpconfig::enable_multisite_full() );
	}

	// ── disable_multisite_full ───────────────────────────────────────────

	public function test_disable_multisite_removes_all_constants(): void {
		$block = "define('WP_ALLOW_MULTISITE', true);\n"
			. "define('MULTISITE', true);\n"
			. "define('SUBDOMAIN_INSTALL', false);\n"
			. "define('DOMAIN_CURRENT_SITE', 'example.com');\n"
			. "define('PATH_CURRENT_SITE', '/');\n"
			. "define('SITE_ID_CURRENT_SITE', 1);\n"
			. "define('BLOG_ID_CURRENT_SITE', 1);\n";
		$this->write_config( "<?php\n" . $block . "// other settings\n" );

		$result = \Network_Central_Wpconfig::disable_multisite_full();
		$this->assertTrue( $result );

		$content  = file_get_contents( $this->config_path );
		$constants = array( 'WP_ALLOW_MULTISITE', 'MULTISITE', 'SUBDOMAIN_INSTALL', 'DOMAIN_CURRENT_SITE', 'PATH_CURRENT_SITE', 'SITE_ID_CURRENT_SITE', 'BLOG_ID_CURRENT_SITE' );
		foreach ( $constants as $const ) {
			$this->assertStringNotContainsString( $const, $content, "Constant should be removed: {$const}" );
		}
	}

	public function test_disable_multisite_preserves_unrelated_defines(): void {
		$this->write_config( "<?php\ndefine('DB_NAME', 'mydb');\ndefine('MULTISITE', true);\ndefine('DB_USER', 'root');\n" );

		\Network_Central_Wpconfig::disable_multisite_full();
		$content = file_get_contents( $this->config_path );

		$this->assertStringContainsString( "define('DB_NAME', 'mydb');", $content );
		$this->assertStringContainsString( "define('DB_USER', 'root');", $content );
	}

	public function test_disable_multisite_normalizes_blank_lines(): void {
		$this->write_config( "<?php\ndefine('MULTISITE', true);\n\n\n\n\n// other\n" );

		\Network_Central_Wpconfig::disable_multisite_full();
		$content = file_get_contents( $this->config_path );

		$this->assertStringNotContainsString( "\n\n\n", $content );
	}

	public function test_disable_multisite_returns_false_when_file_missing(): void {
		if ( file_exists( $this->config_path ) ) {
			unlink( $this->config_path );
		}
		$this->assertFalse( \Network_Central_Wpconfig::disable_multisite_full() );
	}

	// ── is_writable ──────────────────────────────────────────────────────

	public function test_is_writable_returns_true_for_existing_writable_file(): void {
		$this->write_config( '<?php' );
		$this->assertTrue( \Network_Central_Wpconfig::is_writable() );
	}

	public function test_is_writable_returns_false_when_file_absent(): void {
		if ( file_exists( $this->config_path ) ) {
			unlink( $this->config_path );
		}
		$this->assertFalse( \Network_Central_Wpconfig::is_writable() );
	}
}

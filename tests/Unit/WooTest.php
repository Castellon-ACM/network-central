<?php

declare( strict_types=1 );

namespace NetworkCentral\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Tests for Network_Central_Woo option management and class constants.
 */
class WooTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	// ── Constants ────────────────────────────────────────────────────────

	public function test_option_key_constant(): void {
		$this->assertSame( 'network_central_woo_enabled', \Network_Central_Woo::OPTION_KEY );
	}

	public function test_wc_plugin_constant(): void {
		$this->assertSame( 'woocommerce/woocommerce.php', \Network_Central_Woo::WC_PLUGIN );
	}

	public function test_page_slug_constant(): void {
		$this->assertSame( 'network-central-woo', \Network_Central_Woo::PAGE_SLUG );
	}

	public function test_nonce_action_constant(): void {
		$this->assertSame( 'network_central_woo_toggle', \Network_Central_Woo::NONCE_ACTION );
	}

	// ── is_enabled ───────────────────────────────────────────────────────

	public function test_is_enabled_returns_false_when_option_not_set(): void {
		Functions\when( 'get_site_option' )->justReturn( false );
		$this->assertFalse( \Network_Central_Woo::is_enabled() );
	}

	public function test_is_enabled_returns_true_when_option_is_true(): void {
		Functions\when( 'get_site_option' )->justReturn( true );
		$this->assertTrue( \Network_Central_Woo::is_enabled() );
	}

	public function test_is_enabled_casts_truthy_string_to_true(): void {
		Functions\when( 'get_site_option' )->justReturn( '1' );
		$this->assertTrue( \Network_Central_Woo::is_enabled() );
	}

	public function test_is_enabled_casts_zero_string_to_false(): void {
		Functions\when( 'get_site_option' )->justReturn( '0' );
		$this->assertFalse( \Network_Central_Woo::is_enabled() );
	}

	public function test_is_enabled_reads_correct_option_key(): void {
		Functions\expect( 'get_site_option' )
			->once()
			->with( \Network_Central_Woo::OPTION_KEY, false )
			->andReturn( false );

		\Network_Central_Woo::is_enabled();
		$this->addToAssertionCount( 1 );
	}

	// ── set_enabled ──────────────────────────────────────────────────────

	public function test_set_enabled_true_calls_update_site_option_with_true(): void {
		Functions\expect( 'update_site_option' )
			->once()
			->with( \Network_Central_Woo::OPTION_KEY, true );

		\Network_Central_Woo::set_enabled( true );
		$this->addToAssertionCount( 1 );
	}

	public function test_set_enabled_false_calls_update_site_option_with_false(): void {
		Functions\expect( 'update_site_option' )
			->once()
			->with( \Network_Central_Woo::OPTION_KEY, false );

		\Network_Central_Woo::set_enabled( false );
		$this->addToAssertionCount( 1 );
	}

	public function test_set_enabled_casts_truthy_int_to_bool(): void {
		Functions\expect( 'update_site_option' )
			->once()
			->with( \Network_Central_Woo::OPTION_KEY, true );

		\Network_Central_Woo::set_enabled( 1 );
		$this->addToAssertionCount( 1 );
	}

	public function test_set_enabled_casts_falsy_int_to_bool(): void {
		Functions\expect( 'update_site_option' )
			->once()
			->with( \Network_Central_Woo::OPTION_KEY, false );

		\Network_Central_Woo::set_enabled( 0 );
		$this->addToAssertionCount( 1 );
	}
}

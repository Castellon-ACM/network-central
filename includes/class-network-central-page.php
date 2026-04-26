<?php
/**
 * Admin page renderer.
 *
 * @package NetworkCentral
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Network_Central_Page
 */
class Network_Central_Page {

	/**
	 * Render the Network Central admin page.
	 *
	 * @return void
	 */
	public static function render() {
		$is_multisite      = defined( 'MULTISITE' ) && MULTISITE;
		$multisite_allowed = Network_Central_Wpconfig::get_multisite_allowed();
		$wpconfig_writable = Network_Central_Wpconfig::is_writable();
		$htaccess_writable = Network_Central_Htaccess::is_writable();
		$toggle_on         = $is_multisite || $multisite_allowed;
		$can_toggle        = $wpconfig_writable;

		$notice_success = 'rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-emerald-300 text-sm mb-6';
		$notice_error   = 'rounded-lg border border-red-500/40 bg-red-500/10 px-4 py-3 text-red-300 text-sm mb-6';
		?>
		<script src="<?php echo esc_url( NETWORK_CENTRAL_PLUGIN_URL . 'assets/js/tailwind.min.js' ); ?>"></script>
		<script>
			tailwind.config = {
				darkMode: 'class',
				theme: { extend: { fontFamily: { mono: ['JetBrains Mono', 'Consolas', 'monospace'] } } }
			};
		</script>
		<style>
			<?php
			$fonts_url = NETWORK_CENTRAL_PLUGIN_URL . 'fonts/';
			echo "@font-face{font-family:'JetBrains Mono';font-weight:400;font-style:normal;font-display:swap;src:url('" . esc_url( $fonts_url . 'JetBrainsMono-Regular.woff2' ) . "') format('woff2');}";
			echo "@font-face{font-family:'JetBrains Mono';font-weight:500;font-style:normal;font-display:swap;src:url('" . esc_url( $fonts_url . 'JetBrainsMono-Medium.woff2' ) . "') format('woff2');}";
			echo "@font-face{font-family:'JetBrains Mono';font-weight:600;font-style:normal;font-display:swap;src:url('" . esc_url( $fonts_url . 'JetBrainsMono-SemiBold.woff2' ) . "') format('woff2');}";
			?>
		</style>
		<div class="min-h-screen bg-slate-950 text-slate-100 font-mono" style="margin-left:-20px;margin-top:-8px;padding:2rem 2.5rem;box-sizing:border-box;">
			<div class="max-w-2xl mx-auto">

				<header class="border-b border-slate-700/80 pb-6 mb-8">
					<h1 class="text-2xl font-semibold text-cyan-400 tracking-tight flex items-center gap-2">
						<span class="inline-block w-2 h-2 rounded-full bg-cyan-400 animate-pulse" aria-hidden="true"></span>
						<?php esc_html_e( 'Network Central', 'network-central' ); ?>
					</h1>
					<p class="text-slate-500 text-sm mt-1"><?php esc_html_e( 'WordPress Multisite management', 'network-central' ); ?></p>
				</header>

				<?php
				$nc_ok  = isset( $_GET['nc_ok'] ) ? sanitize_key( wp_unslash( $_GET['nc_ok'] ) ) : '';
				$nc_err = isset( $_GET['nc_err'] ) ? sanitize_key( wp_unslash( $_GET['nc_err'] ) ) : '';

				if ( 'woo_saved' === $nc_ok ) {
					echo '<div class="' . esc_attr( $notice_success ) . '">' . esc_html__( 'WooCommerce network management setting saved.', 'network-central' ) . '</div>';
				}
				if ( 'enabled' === $nc_ok ) {
					echo '<div class="' . esc_attr( $notice_success ) . '">' . esc_html__( 'Multisite enabled successfully. wp-config.php has been updated and the network tables have been created.', 'network-central' ) . '</div>';
				}
				if ( 'disabled' === $nc_ok ) {
					echo '<div class="' . esc_attr( $notice_success ) . '">' . esc_html__( 'Multisite disabled. wp-config.php and .htaccess have been restored to single-site.', 'network-central' ) . '</div>';
				}
				if ( 'not_writable' === $nc_err ) {
					echo '<div class="' . esc_attr( $notice_error ) . '">' . esc_html__( 'wp-config.php is not writable. Fix the file permissions and try again.', 'network-central' ) . '</div>';
				}
				if ( 'write_failed' === $nc_err ) {
					echo '<div class="' . esc_attr( $notice_error ) . '">' . esc_html__( 'Failed to write wp-config.php or .htaccess. Check file permissions.', 'network-central' ) . '</div>';
				}
				?>

				<div class="rounded-xl border border-slate-700/80 bg-slate-900/60 backdrop-blur px-6 py-8 shadow-lg shadow-cyan-500/5">

					<?php if ( $is_multisite ) : ?>
						<div class="mb-6 flex items-center gap-3">
							<span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/15 border border-emerald-500/40 text-emerald-300 text-xs font-semibold">
								<span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse inline-block"></span>
								<?php esc_html_e( 'Multisite active', 'network-central' ); ?>
							</span>
						</div>
					<?php else : ?>
						<div class="mb-6 flex items-center gap-3">
							<span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-700/60 border border-slate-600 text-slate-400 text-xs font-semibold">
								<span class="w-1.5 h-1.5 rounded-full bg-slate-500 inline-block"></span>
								<?php esc_html_e( 'Single site', 'network-central' ); ?>
							</span>
						</div>
					<?php endif; ?>

					<form method="post" action="<?php echo esc_url( add_query_arg( 'page', NETWORK_CENTRAL_PAGE_SLUG, network_central_admin_url() ) ); ?>">
						<?php wp_nonce_field( NETWORK_CENTRAL_NONCE_ACTION, 'network_central_nonce' ); ?>

						<div class="space-y-2">
							<label class="flex items-center gap-4 cursor-pointer group <?php echo ! $can_toggle ? 'opacity-60 cursor-not-allowed' : ''; ?>">
								<div class="relative flex-shrink-0">
									<input type="checkbox" name="network_central_multisite" value="1"
										<?php checked( $toggle_on ); ?>
										<?php echo ! $can_toggle ? 'disabled' : ''; ?>
										class="sr-only peer">
									<span class="block w-14 h-8 rounded-full bg-slate-700 border border-slate-600 transition-all
										peer-focus-visible:ring-2 peer-focus-visible:ring-cyan-400/50
										after:content-[''] after:absolute after:top-1 after:left-1
										after:w-6 after:h-6 after:rounded-full after:bg-slate-300 after:transition-all after:shadow
										peer-checked:after:translate-x-6 peer-checked:after:bg-cyan-400
										peer-checked:bg-cyan-500/20 peer-checked:border-cyan-500/60"></span>
								</div>
								<div>
									<p class="text-slate-100 font-semibold text-base group-hover:text-white transition">
										<?php esc_html_e( 'Enable WordPress Multisite', 'network-central' ); ?>
									</p>
									<p class="text-slate-500 text-sm mt-0.5">
										<?php esc_html_e( 'Subdirectory install. Writes constants to wp-config.php, updates .htaccess rewrite rules, and creates the network tables.', 'network-central' ); ?>
									</p>
								</div>
							</label>

							<?php if ( ! $wpconfig_writable ) : ?>
								<p class="text-red-400 text-sm mt-2">
									<?php esc_html_e( 'wp-config.php is not writable. Fix file permissions to enable this option.', 'network-central' ); ?>
								</p>
							<?php endif; ?>
						</div>

						<?php if ( $can_toggle ) : ?>
							<div class="mt-8 pt-6 border-t border-slate-700/60">
								<button type="submit"
									class="px-5 py-2.5 rounded-lg bg-cyan-500/20 border border-cyan-400/60 text-cyan-300 font-medium hover:bg-cyan-500/30 hover:shadow-[0_0_12px_rgba(34,211,238,0.15)] transition">
									<?php esc_html_e( 'Save', 'network-central' ); ?>
								</button>
							</div>
						<?php endif; ?>
					</form>

					<?php if ( $is_multisite ) : ?>
						<div class="mt-8 pt-6 border-t border-slate-700/60">
							<a href="<?php echo esc_url( admin_url( 'network.php' ) ); ?>"
								class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-600 text-slate-300 hover:bg-slate-800 hover:border-slate-500 transition text-sm">
								<?php esc_html_e( 'Go to Network Admin', 'network-central' ); ?>
								<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
								</svg>
							</a>
						</div>
					<?php elseif ( $multisite_allowed && ! $is_multisite ) : ?>
						<div class="mt-6 rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-3">
							<p class="text-amber-300 text-sm font-medium mb-1"><?php esc_html_e( 'WP_ALLOW_MULTISITE is set but the network is not fully installed yet.', 'network-central' ); ?></p>
							<p class="text-amber-200/80 text-sm mb-3"><?php esc_html_e( 'Complete the network setup from the WordPress screen.', 'network-central' ); ?></p>
							<a href="<?php echo esc_url( admin_url( 'network/setup.php' ) ); ?>"
								class="inline-flex items-center px-4 py-2 rounded-lg bg-cyan-500/20 border border-cyan-400/60 text-cyan-300 text-sm font-medium hover:bg-cyan-500/30 transition">
								<?php esc_html_e( 'Go to Tools → Network Setup', 'network-central' ); ?>
							</a>
						</div>
					<?php endif; ?>

				</div>

				<?php if ( $is_multisite ) : ?>
				<?php
				$woo_sites   = Network_Central_Woo::get_woo_sites();
				$woo_enabled = Network_Central_Woo::is_enabled();
				?>
				<div class="mt-6 rounded-xl border border-slate-700/60 bg-slate-900/40 px-6 py-5">
					<h2 class="text-sm font-semibold text-slate-400 uppercase tracking-widest mb-4"><?php esc_html_e( 'WooCommerce network management', 'network-central' ); ?></h2>

					<?php if ( empty( $woo_sites ) ) : ?>
						<p class="text-slate-500 text-sm"><?php esc_html_e( 'WooCommerce is not active on any site in this network.', 'network-central' ); ?></p>
					<?php else : ?>
						<p class="text-slate-400 text-sm mb-4">
							<?php
							printf(
								/* translators: %d: number of sites with WooCommerce */
								esc_html__( 'WooCommerce detected on %d site(s). Enable network management to view and edit all products from one place.', 'network-central' ),
								count( $woo_sites )
							);
							?>
						</p>

						<form method="post" action="<?php echo esc_url( add_query_arg( 'page', NETWORK_CENTRAL_PAGE_SLUG, network_central_admin_url() ) ); ?>">
							<?php wp_nonce_field( Network_Central_Woo::NONCE_ACTION, 'network_central_woo_nonce' ); ?>

							<label class="flex items-center gap-4 cursor-pointer group mb-4">
								<div class="relative flex-shrink-0">
									<input type="checkbox" name="network_central_woo" value="1"
										<?php checked( $woo_enabled ); ?>
										class="sr-only peer">
									<span class="block w-14 h-8 rounded-full bg-slate-700 border border-slate-600 transition-all
										peer-focus-visible:ring-2 peer-focus-visible:ring-cyan-400/50
										after:content-[''] after:absolute after:top-1 after:left-1
										after:w-6 after:h-6 after:rounded-full after:bg-slate-300 after:transition-all after:shadow
										peer-checked:after:translate-x-6 peer-checked:after:bg-cyan-400
										peer-checked:bg-cyan-500/20 peer-checked:border-cyan-500/60"></span>
								</div>
								<div>
									<p class="text-slate-100 font-semibold text-base group-hover:text-white transition">
										<?php esc_html_e( 'Enable network product management', 'network-central' ); ?>
									</p>
									<p class="text-slate-500 text-sm mt-0.5">
										<?php esc_html_e( 'Adds a WooCommerce Manager to handle products, orders, customers and coupons across every site from the Network Admin.', 'network-central' ); ?>
									</p>
								</div>
							</label>

							<button type="submit"
								class="px-5 py-2 rounded-lg bg-cyan-500/20 border border-cyan-400/60 text-cyan-300 text-sm font-medium hover:bg-cyan-500/30 transition">
								<?php esc_html_e( 'Save', 'network-central' ); ?>
							</button>
						</form>

						<?php if ( $woo_enabled ) : ?>
							<div class="mt-4 pt-4 border-t border-slate-700/60">
								<a href="<?php echo esc_url( network_admin_url( 'admin.php?page=' . Network_Central_Woo::PAGE_SLUG ) ); ?>"
									class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-600 text-slate-300 hover:bg-slate-800 hover:border-slate-500 transition text-sm">
									<?php esc_html_e( 'Go to WooCommerce Manager', 'network-central' ); ?>
									<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
									</svg>
								</a>
							</div>
						<?php endif; ?>
					<?php endif; ?>
				</div>
				<?php endif; ?>

				<div class="mt-6 rounded-xl border border-slate-700/60 bg-slate-900/40 px-6 py-5">
					<h2 class="text-sm font-semibold text-slate-400 uppercase tracking-widest mb-4"><?php esc_html_e( 'System status', 'network-central' ); ?></h2>
					<dl class="space-y-3 text-sm">
						<?php self::render_status_row( __( 'WordPress Multisite', 'network-central' ), $is_multisite ? __( 'Active', 'network-central' ) : __( 'Inactive', 'network-central' ), $is_multisite ); ?>
						<?php self::render_status_row( __( 'wp-config.php writable', 'network-central' ), $wpconfig_writable ? __( 'Yes', 'network-central' ) : __( 'No', 'network-central' ), $wpconfig_writable ); ?>
						<?php self::render_status_row( __( '.htaccess writable', 'network-central' ), $htaccess_writable ? __( 'Yes', 'network-central' ) : __( 'No — manual rewrite rules required', 'network-central' ), $htaccess_writable ); ?>
						<?php
						self::render_status_row( __( 'PHP version', 'network-central' ), phpversion(), true );
						self::render_status_row( __( 'WordPress version', 'network-central' ), get_bloginfo( 'version' ), true );
						?>
					</dl>
				</div>

			</div>
		</div>
		<?php
	}

	/**
	 * Render a status row in the system status panel.
	 *
	 * @param string $label Row label.
	 * @param string $value Row value (plain text, will be escaped).
	 * @param bool   $ok    Green dot when true, red dot when false.
	 * @return void
	 */
	private static function render_status_row( $label, $value, $ok ) {
		$dot = $ok
			? '<span class="w-2 h-2 rounded-full bg-emerald-400 inline-block flex-shrink-0 mt-0.5"></span>'
			: '<span class="w-2 h-2 rounded-full bg-red-400 inline-block flex-shrink-0 mt-0.5"></span>';
		echo '<div class="flex items-start gap-3">'
			. $dot // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			. '<dt class="text-slate-500 w-52 flex-shrink-0">' . esc_html( $label ) . '</dt>'
			. '<dd class="text-slate-200">' . esc_html( $value ) . '</dd>'
			. '</div>';
	}
}

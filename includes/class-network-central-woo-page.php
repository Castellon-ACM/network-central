<?php
/**
 * Network Products admin page renderer.
 *
 * @package NetworkCentral
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Network_Central_Woo_Page
 */
class Network_Central_Woo_Page {

	/**
	 * Render the Network Products page.
	 *
	 * @return void
	 */
	public static function render() {
		$woo_sites   = Network_Central_Woo::get_woo_sites();
		$filter_site = isset( $_GET['nc_site'] ) ? (int) $_GET['nc_site'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$paged       = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$per_page    = 25;
		$products    = Network_Central_Woo::get_products( $filter_site, $per_page, $paged );
		$total       = Network_Central_Woo::count_products( $filter_site );
		$total_pages = $total > 0 ? (int) ceil( $total / $per_page ) : 1;
		$base_url    = network_admin_url( 'admin.php?page=network-central-products' );
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
			<div class="max-w-6xl mx-auto">

				<header class="border-b border-slate-700/80 pb-6 mb-8 flex items-center justify-between">
					<div>
						<h1 class="text-2xl font-semibold text-cyan-400 tracking-tight flex items-center gap-2">
							<span class="inline-block w-2 h-2 rounded-full bg-cyan-400 animate-pulse" aria-hidden="true"></span>
							<?php esc_html_e( 'Network Products', 'network-central' ); ?>
						</h1>
						<p class="text-slate-500 text-sm mt-1"><?php esc_html_e( 'WooCommerce products across all network sites', 'network-central' ); ?></p>
					</div>
					<a href="<?php echo esc_url( network_admin_url( 'admin.php?page=' . NETWORK_CENTRAL_PAGE_SLUG ) ); ?>"
						class="text-slate-500 hover:text-slate-300 text-sm transition">
						← <?php esc_html_e( 'Network Central', 'network-central' ); ?>
					</a>
				</header>

				<?php if ( empty( $woo_sites ) ) : ?>
					<div class="rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-amber-300 text-sm">
						<?php esc_html_e( 'WooCommerce is not active on any site in this network.', 'network-central' ); ?>
					</div>
				<?php else : ?>

					<!-- Filter bar -->
					<div class="mb-6 flex items-center gap-4 flex-wrap">
						<form method="get" action="<?php echo esc_url( $base_url ); ?>" class="flex items-center gap-3">
							<input type="hidden" name="page" value="network-central-products">
							<label class="text-slate-400 text-sm"><?php esc_html_e( 'Site', 'network-central' ); ?></label>
							<select name="nc_site"
								class="bg-slate-800 border border-slate-600 text-slate-200 text-sm rounded-lg px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-cyan-500">
								<option value="0" <?php selected( 0, $filter_site ); ?>><?php esc_html_e( 'All sites', 'network-central' ); ?></option>
								<?php foreach ( $woo_sites as $wsite ) : ?>
									<?php
									switch_to_blog( $wsite->blog_id );
									$wsite_name = get_bloginfo( 'name' );
									restore_current_blog();
									?>
									<option value="<?php echo (int) $wsite->blog_id; ?>" <?php selected( (int) $wsite->blog_id, $filter_site ); ?>>
										<?php echo esc_html( $wsite_name ); ?> (ID: <?php echo (int) $wsite->blog_id; ?>)
									</option>
								<?php endforeach; ?>
							</select>
							<button type="submit"
								class="px-3 py-1.5 rounded-lg bg-cyan-500/20 border border-cyan-400/60 text-cyan-300 text-sm hover:bg-cyan-500/30 transition">
								<?php esc_html_e( 'Filter', 'network-central' ); ?>
							</button>
						</form>

						<span class="text-slate-500 text-sm ml-auto">
							<?php
							printf(
								/* translators: %d: total product count */
								esc_html__( '%d products total', 'network-central' ),
								(int) $total
							);
							?>
						</span>
					</div>

					<!-- Products table -->
					<div class="rounded-xl border border-slate-700/60 bg-slate-900/60 overflow-hidden shadow-lg">
						<?php if ( empty( $products ) ) : ?>
							<div class="px-6 py-10 text-center text-slate-500 text-sm">
								<?php esc_html_e( 'No products found.', 'network-central' ); ?>
							</div>
						<?php else : ?>
							<table class="w-full text-sm">
								<thead>
									<tr class="border-b border-slate-700/80 text-slate-400 text-xs uppercase tracking-wider">
										<th class="px-4 py-3 text-left font-medium"><?php esc_html_e( 'Product', 'network-central' ); ?></th>
										<th class="px-4 py-3 text-left font-medium"><?php esc_html_e( 'SKU', 'network-central' ); ?></th>
										<th class="px-4 py-3 text-left font-medium"><?php esc_html_e( 'Price', 'network-central' ); ?></th>
										<th class="px-4 py-3 text-left font-medium"><?php esc_html_e( 'Stock', 'network-central' ); ?></th>
										<th class="px-4 py-3 text-left font-medium"><?php esc_html_e( 'Status', 'network-central' ); ?></th>
										<th class="px-4 py-3 text-left font-medium"><?php esc_html_e( 'Site', 'network-central' ); ?></th>
										<th class="px-4 py-3 text-left font-medium"><?php esc_html_e( 'Actions', 'network-central' ); ?></th>
									</tr>
								</thead>
								<tbody class="divide-y divide-slate-700/40">
									<?php foreach ( $products as $p ) : ?>
										<tr class="hover:bg-slate-800/40 transition">
											<td class="px-4 py-3 text-slate-100 font-medium">
												<?php echo esc_html( $p['title'] ); ?>
											</td>
											<td class="px-4 py-3 text-slate-400">
												<?php echo $p['sku'] ? esc_html( $p['sku'] ) : '<span class="text-slate-600">—</span>'; ?>
											</td>
											<td class="px-4 py-3 text-slate-300">
												<?php echo $p['price'] !== null && $p['price'] !== '' ? esc_html( $p['price'] ) : '<span class="text-slate-600">—</span>'; ?>
											</td>
											<td class="px-4 py-3">
												<?php if ( 'instock' === $p['stock'] ) : ?>
													<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-500/15 border border-emerald-500/40 text-emerald-300 text-xs">
														<?php esc_html_e( 'In stock', 'network-central' ); ?>
													</span>
												<?php elseif ( 'outofstock' === $p['stock'] ) : ?>
													<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-red-500/15 border border-red-500/40 text-red-300 text-xs">
														<?php esc_html_e( 'Out of stock', 'network-central' ); ?>
													</span>
												<?php else : ?>
													<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-500/15 border border-amber-500/40 text-amber-300 text-xs">
														<?php esc_html_e( 'On backorder', 'network-central' ); ?>
													</span>
												<?php endif; ?>
											</td>
											<td class="px-4 py-3">
												<?php if ( 'publish' === $p['status'] ) : ?>
													<span class="text-emerald-400 text-xs"><?php esc_html_e( 'Published', 'network-central' ); ?></span>
												<?php elseif ( 'draft' === $p['status'] ) : ?>
													<span class="text-slate-400 text-xs"><?php esc_html_e( 'Draft', 'network-central' ); ?></span>
												<?php else : ?>
													<span class="text-amber-400 text-xs"><?php echo esc_html( $p['status'] ); ?></span>
												<?php endif; ?>
											</td>
											<td class="px-4 py-3 text-slate-400 text-xs">
												<?php echo esc_html( $p['site_name'] ); ?>
											</td>
											<td class="px-4 py-3">
												<a href="<?php echo esc_url( $p['edit_url'] ); ?>"
													class="text-cyan-400 hover:text-cyan-300 text-xs transition underline underline-offset-2">
													<?php esc_html_e( 'Edit', 'network-central' ); ?>
												</a>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>

							<!-- Pagination -->
							<?php if ( $total_pages > 1 ) : ?>
								<div class="border-t border-slate-700/60 px-4 py-3 flex items-center justify-between">
									<span class="text-slate-500 text-xs">
										<?php
										printf(
											/* translators: 1: current page, 2: total pages */
											esc_html__( 'Page %1$d of %2$d', 'network-central' ),
											(int) $paged,
											(int) $total_pages
										);
										?>
									</span>
									<div class="flex gap-2">
										<?php if ( $paged > 1 ) : ?>
											<a href="<?php echo esc_url( add_query_arg( array( 'nc_site' => $filter_site, 'paged' => $paged - 1 ), $base_url ) ); ?>"
												class="px-3 py-1 rounded border border-slate-600 text-slate-300 hover:bg-slate-800 text-xs transition">
												← <?php esc_html_e( 'Prev', 'network-central' ); ?>
											</a>
										<?php endif; ?>
										<?php if ( $paged < $total_pages ) : ?>
											<a href="<?php echo esc_url( add_query_arg( array( 'nc_site' => $filter_site, 'paged' => $paged + 1 ), $base_url ) ); ?>"
												class="px-3 py-1 rounded border border-slate-600 text-slate-300 hover:bg-slate-800 text-xs transition">
												<?php esc_html_e( 'Next', 'network-central' ); ?> →
											</a>
										<?php endif; ?>
									</div>
								</div>
							<?php endif; ?>
						<?php endif; ?>
					</div>

				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}

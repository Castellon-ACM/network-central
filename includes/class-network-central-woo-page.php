<?php
/**
 * WooCommerce Network Manager page renderer.
 *
 * @package NetworkCentral
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Network_Central_Woo_Page
 */
class Network_Central_Woo_Page {

	/**
	 * Render the WooCommerce Network Manager page.
	 *
	 * @return void
	 */
	public static function render() {
		$woo_sites   = Network_Central_Woo::get_woo_sites();
		$active_tab  = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'products'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$filter_site = isset( $_GET['nc_site'] ) ? (int) $_GET['nc_site'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$paged       = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$per_page    = 25;
		$base_url    = network_admin_url( 'admin.php?page=' . Network_Central_Woo::PAGE_SLUG );

		$tabs = array(
			'products'  => __( 'Products', 'network-central' ),
			'orders'    => __( 'Orders', 'network-central' ),
			'customers' => __( 'Customers', 'network-central' ),
			'coupons'   => __( 'Coupons', 'network-central' ),
		);

		// Load data for active tab.
		switch ( $active_tab ) {
			case 'orders':
				$rows  = Network_Central_Woo::get_orders( $filter_site, $per_page, $paged );
				$total = Network_Central_Woo::count_orders( $filter_site );
				break;
			case 'customers':
				$rows  = Network_Central_Woo::get_customers( $filter_site, $per_page, $paged );
				$total = Network_Central_Woo::count_customers( $filter_site );
				break;
			case 'coupons':
				$rows  = Network_Central_Woo::get_coupons( $filter_site, $per_page, $paged );
				$total = Network_Central_Woo::count_coupons( $filter_site );
				break;
			default:
				$active_tab = 'products';
				$rows       = Network_Central_Woo::get_products( $filter_site, $per_page, $paged );
				$total      = Network_Central_Woo::count_products( $filter_site );
				break;
		}

		$total_pages = $total > 0 ? (int) ceil( $total / $per_page ) : 1;
		?>
		<div class="nc-breakout min-h-screen bg-slate-950 text-slate-100 font-mono">
		<div class="max-w-7xl mx-auto">

			<!-- Header -->
			<header class="border-b border-slate-700/80 pb-6 mb-6 flex items-center justify-between">
				<div>
					<h1 class="text-2xl font-semibold text-cyan-400 tracking-tight flex items-center gap-2">
						<span class="inline-block w-2 h-2 rounded-full bg-cyan-400 animate-pulse" aria-hidden="true"></span>
						<?php esc_html_e( 'WooCommerce Network Manager', 'network-central' ); ?>
					</h1>
					<p class="text-slate-500 text-sm mt-1"><?php esc_html_e( 'Manage WooCommerce data across all network sites', 'network-central' ); ?></p>
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

			<!-- Tabs + filter bar -->
			<div class="flex items-center justify-between mb-6 flex-wrap gap-4">

				<!-- Tabs -->
				<nav class="flex gap-1 bg-slate-900/60 border border-slate-700/60 rounded-lg p-1">
					<?php foreach ( $tabs as $slug => $label ) : ?>
						<?php
						$tab_url    = add_query_arg( array( 'tab' => $slug, 'nc_site' => $filter_site, 'paged' => 1 ), $base_url );
						$is_active  = $active_tab === $slug;
						$tab_class  = $is_active
							? 'px-4 py-1.5 rounded-md bg-cyan-500/20 border border-cyan-400/50 text-cyan-300 text-sm font-medium'
							: 'px-4 py-1.5 rounded-md text-slate-400 hover:text-slate-200 text-sm transition';
						?>
						<a href="<?php echo esc_url( $tab_url ); ?>" class="<?php echo esc_attr( $tab_class ); ?>">
							<?php echo esc_html( $label ); ?>
						</a>
					<?php endforeach; ?>
				</nav>

				<!-- Site filter -->
				<form method="get" class="flex items-center gap-2">
					<input type="hidden" name="page" value="<?php echo esc_attr( Network_Central_Woo::PAGE_SLUG ); ?>">
					<input type="hidden" name="tab" value="<?php echo esc_attr( $active_tab ); ?>">
					<select name="nc_site"
						class="border border-slate-600 text-sm rounded-lg px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-cyan-500">
						<option value="0" <?php selected( 0, $filter_site ); ?>><?php esc_html_e( 'All sites', 'network-central' ); ?></option>
						<?php foreach ( $woo_sites as $ws ) : ?>
							<?php
							switch_to_blog( $ws->blog_id );
							$ws_name = get_bloginfo( 'name' );
							restore_current_blog();
							?>
							<option value="<?php echo (int) $ws->blog_id; ?>" <?php selected( (int) $ws->blog_id, $filter_site ); ?>>
								<?php echo esc_html( $ws_name ); ?> (ID:<?php echo (int) $ws->blog_id; ?>)
							</option>
						<?php endforeach; ?>
					</select>
					<button type="submit" class="px-3 py-1.5 rounded-lg bg-slate-700 border border-slate-600 text-slate-300 text-sm hover:bg-slate-600 transition">
						<?php esc_html_e( 'Filter', 'network-central' ); ?>
					</button>
					<span class="text-slate-600 text-xs ml-2">
						<?php
						printf(
							/* translators: %d: total count */
							esc_html__( '%d total', 'network-central' ),
							(int) $total
						);
						?>
					</span>
				</form>
			</div>

			<!-- Table -->
			<div class="rounded-xl border border-slate-700/60 bg-slate-900/60 overflow-hidden shadow-lg">
				<?php if ( empty( $rows ) ) : ?>
					<div class="px-6 py-12 text-center text-slate-500 text-sm">
						<?php esc_html_e( 'No records found.', 'network-central' ); ?>
					</div>
				<?php else : ?>
					<div class="overflow-x-auto">
					<table class="w-full text-sm">
						<thead>
							<tr class="border-b border-slate-700/80 text-slate-400 text-xs uppercase tracking-wider">
								<?php self::render_thead( $active_tab ); ?>
							</tr>
						</thead>
						<tbody class="divide-y divide-slate-700/40">
							<?php foreach ( $rows as $row ) : ?>
								<tr class="hover:bg-slate-800/40 transition">
									<?php self::render_row( $active_tab, $row ); ?>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					</div>

					<!-- Pagination -->
					<?php if ( $total_pages > 1 ) : ?>
						<div class="border-t border-slate-700/60 px-4 py-3 flex items-center justify-between">
							<span class="text-slate-500 text-xs">
								<?php printf( esc_html__( 'Page %1$d of %2$d', 'network-central' ), (int) $paged, (int) $total_pages ); ?>
							</span>
							<div class="flex gap-2">
								<?php if ( $paged > 1 ) : ?>
									<a href="<?php echo esc_url( add_query_arg( array( 'tab' => $active_tab, 'nc_site' => $filter_site, 'paged' => $paged - 1 ), $base_url ) ); ?>"
										class="px-3 py-1 rounded border border-slate-600 text-slate-300 hover:bg-slate-800 text-xs transition">
										← <?php esc_html_e( 'Prev', 'network-central' ); ?>
									</a>
								<?php endif; ?>
								<?php if ( $paged < $total_pages ) : ?>
									<a href="<?php echo esc_url( add_query_arg( array( 'tab' => $active_tab, 'nc_site' => $filter_site, 'paged' => $paged + 1 ), $base_url ) ); ?>"
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

	// ── Table heads ───────────────────────────────────────────────────────

	private static function render_thead( $tab ) {
		$cols = array(
			'products'  => array( 'Product', 'SKU', 'Price', 'Stock', 'Status', 'Site', '' ),
			'orders'    => array( '#', 'Date', 'Customer', 'Email', 'Total', 'Status', 'Site', '' ),
			'customers' => array( 'Name', 'Email', 'Country', 'Registered', 'Site', '' ),
			'coupons'   => array( 'Code', 'Type', 'Amount', 'Used', 'Limit', 'Expires', 'Site', '' ),
		);
		$headers = isset( $cols[ $tab ] ) ? $cols[ $tab ] : array();
		foreach ( $headers as $h ) {
			echo '<th class="px-4 py-3 text-left font-medium">' . esc_html( $h ? __( $h, 'network-central' ) : '' ) . '</th>'; // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
		}
	}

	// ── Table rows ────────────────────────────────────────────────────────

	private static function render_row( $tab, $r ) {
		switch ( $tab ) {
			case 'products':
				self::cell( $r['title'], 'text-slate-100 font-medium' );
				self::cell( $r['sku'] ?: '—', 'text-slate-400' );
				self::cell( $r['price'] !== null && $r['price'] !== '' ? $r['price'] : '—', 'text-slate-300' );
				echo '<td class="px-4 py-3">' . self::stock_badge( $r['stock'] ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '<td class="px-4 py-3">' . self::status_badge( $r['status'] ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				self::cell( $r['site_name'], 'text-slate-500 text-xs' );
				self::edit_cell( $r['edit_url'] );
				break;

			case 'orders':
				self::cell( '#' . $r['id'], 'text-slate-300 font-medium' );
				self::cell( $r['date'] ? date_i18n( get_option( 'date_format' ), strtotime( $r['date'] ) ) : '—', 'text-slate-400 text-xs' );
				self::cell( $r['name'] ?: '—', 'text-slate-200' );
				self::cell( $r['email'] ?: '—', 'text-slate-400 text-xs' );
				self::cell( $r['total'] !== '' ? $r['total'] : '—', 'text-slate-300' );
				echo '<td class="px-4 py-3">' . self::order_status_badge( $r['status'] ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				self::cell( $r['site_name'], 'text-slate-500 text-xs' );
				self::edit_cell( $r['edit_url'] );
				break;

			case 'customers':
				self::cell( $r['name'] ?: $r['email'], 'text-slate-100 font-medium' );
				self::cell( $r['email'], 'text-slate-400 text-xs' );
				self::cell( $r['country'] ?: '—', 'text-slate-400' );
				self::cell( $r['registered'] ? date_i18n( get_option( 'date_format' ), strtotime( $r['registered'] ) ) : '—', 'text-slate-400 text-xs' );
				self::cell( $r['site_name'], 'text-slate-500 text-xs' );
				self::edit_cell( $r['edit_url'] );
				break;

			case 'coupons':
				echo '<td class="px-4 py-3"><span class="font-mono text-cyan-300 font-semibold">' . esc_html( $r['code'] ) . '</span></td>';
				self::cell( self::coupon_type_label( $r['type'] ), 'text-slate-400 text-xs' );
				self::cell( $r['amount'] !== null && $r['amount'] !== '' ? $r['amount'] : '—', 'text-slate-300' );
				self::cell( (string) $r['usage_count'], 'text-slate-400 text-xs' );
				self::cell( (string) $r['usage_limit'], 'text-slate-400 text-xs' );
				self::cell( $r['expires'] ?: '—', 'text-slate-400 text-xs' );
				self::cell( $r['site_name'], 'text-slate-500 text-xs' );
				self::edit_cell( $r['edit_url'] );
				break;
		}
	}

	// ── Helpers ───────────────────────────────────────────────────────────

	private static function cell( $value, $class = 'text-slate-300' ) {
		echo '<td class="px-4 py-3 ' . esc_attr( $class ) . '">' . esc_html( $value ) . '</td>';
	}

	private static function edit_cell( $url ) {
		echo '<td class="px-4 py-3"><a href="' . esc_url( $url ) . '" class="text-cyan-400 hover:text-cyan-300 text-xs transition underline underline-offset-2">' . esc_html__( 'Edit', 'network-central' ) . '</a></td>';
	}

	private static function stock_badge( $stock ) {
		if ( 'instock' === $stock ) {
			return '<span class="px-2 py-0.5 rounded-full bg-emerald-500/15 border border-emerald-500/40 text-emerald-300 text-xs">' . esc_html__( 'In stock', 'network-central' ) . '</span>';
		}
		if ( 'outofstock' === $stock ) {
			return '<span class="px-2 py-0.5 rounded-full bg-red-500/15 border border-red-500/40 text-red-300 text-xs">' . esc_html__( 'Out of stock', 'network-central' ) . '</span>';
		}
		return '<span class="px-2 py-0.5 rounded-full bg-amber-500/15 border border-amber-500/40 text-amber-300 text-xs">' . esc_html__( 'On backorder', 'network-central' ) . '</span>';
	}

	private static function status_badge( $status ) {
		$map = array(
			'publish' => array( 'text-emerald-400', __( 'Published', 'network-central' ) ),
			'draft'   => array( 'text-slate-400',   __( 'Draft', 'network-central' ) ),
			'pending' => array( 'text-amber-400',   __( 'Pending', 'network-central' ) ),
			'private' => array( 'text-purple-400',  __( 'Private', 'network-central' ) ),
		);
		$s = isset( $map[ $status ] ) ? $map[ $status ] : array( 'text-slate-500', ucfirst( $status ) );
		return '<span class="' . esc_attr( $s[0] ) . ' text-xs">' . esc_html( $s[1] ) . '</span>';
	}

	private static function order_status_badge( $status ) {
		$map = array(
			'completed'  => 'bg-emerald-500/15 border-emerald-500/40 text-emerald-300',
			'processing' => 'bg-cyan-500/15 border-cyan-500/40 text-cyan-300',
			'pending'    => 'bg-amber-500/15 border-amber-500/40 text-amber-300',
			'on-hold'    => 'bg-yellow-500/15 border-yellow-500/40 text-yellow-300',
			'cancelled'  => 'bg-slate-700/60 border-slate-600 text-slate-400',
			'refunded'   => 'bg-purple-500/15 border-purple-500/40 text-purple-300',
			'failed'     => 'bg-red-500/15 border-red-500/40 text-red-300',
		);
		$cls = isset( $map[ $status ] ) ? $map[ $status ] : 'bg-slate-700/60 border-slate-600 text-slate-400';
		return '<span class="px-2 py-0.5 rounded-full border text-xs ' . esc_attr( $cls ) . '">' . esc_html( ucfirst( $status ) ) . '</span>';
	}

	private static function coupon_type_label( $type ) {
		$map = array(
			'percent'       => __( '% discount', 'network-central' ),
			'fixed_cart'    => __( 'Fixed cart', 'network-central' ),
			'fixed_product' => __( 'Fixed product', 'network-central' ),
		);
		return isset( $map[ $type ] ) ? $map[ $type ] : ( $type ?: '—' );
	}
}

<?php
/**
 * CPT Network Manager page renderer.
 *
 * @package NetworkCentral
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Network_Central_Cpt_Page
 */
class Network_Central_Cpt_Page {

	/**
	 * Render the CPT Network Manager page.
	 *
	 * @return void
	 */
	public static function render() {
		$all_sites   = Network_Central_Cpt::get_all_sites();
		$filter_site = isset( $_GET['nc_site'] ) ? (int) $_GET['nc_site'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$paged       = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$per_page    = 25;
		$base_url    = network_admin_url( 'admin.php?page=' . Network_Central_Cpt::PAGE_SLUG );

		$cpt_types   = Network_Central_Cpt::get_cpt_types( $filter_site );
		$active_type = isset( $_GET['nc_type'] ) ? sanitize_key( $_GET['nc_type'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $active_type || ! isset( $cpt_types[ $active_type ] ) ) {
			$active_type = $cpt_types ? array_key_first( $cpt_types ) : '';
		}

		$rows  = array();
		$total = 0;
		if ( $active_type ) {
			$rows  = Network_Central_Cpt::get_cpt_posts( $active_type, $filter_site, $per_page, $paged );
			$total = Network_Central_Cpt::count_cpt_posts( $active_type, $filter_site );
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
						<?php esc_html_e( 'CPT Network Manager', 'network-central' ); ?>
					</h1>
					<p class="text-slate-500 text-sm mt-1"><?php esc_html_e( 'Manage custom post types across all network sites', 'network-central' ); ?></p>
				</div>
				<a href="<?php echo esc_url( network_admin_url( 'admin.php?page=' . NETWORK_CENTRAL_PAGE_SLUG ) ); ?>"
					class="text-slate-500 hover:text-slate-300 text-sm transition">
					← <?php esc_html_e( 'Network Central', 'network-central' ); ?>
				</a>
			</header>

			<?php if ( empty( $cpt_types ) ) : ?>
				<div class="rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-amber-300 text-sm">
					<?php esc_html_e( 'No public custom post types found across the network.', 'network-central' ); ?>
				</div>
			<?php else : ?>

			<!-- CPT tabs + filter bar -->
			<div class="flex items-center justify-between mb-6 flex-wrap gap-4">

				<!-- CPT type tabs -->
				<nav class="flex gap-1 bg-slate-900/60 border border-slate-700/60 rounded-lg p-1 flex-wrap">
					<?php foreach ( $cpt_types as $slug => $label ) : ?>
						<?php
						$tab_url   = add_query_arg( array( 'nc_type' => $slug, 'nc_site' => $filter_site, 'paged' => 1 ), $base_url );
						$tab_class = $active_type === $slug
							? 'px-4 py-1.5 rounded-md bg-cyan-500/20 border border-cyan-400/50 text-cyan-300 text-sm font-medium'
							: 'px-4 py-1.5 rounded-md text-slate-400 hover:text-slate-200 text-sm transition';
						?>
						<a href="<?php echo esc_url( $tab_url ); ?>" class="<?php echo esc_attr( $tab_class ); ?>">
							<?php echo esc_html( $label ); ?>
							<span class="text-slate-600 text-xs ml-1"><?php echo esc_html( $slug ); ?></span>
						</a>
					<?php endforeach; ?>
				</nav>

				<!-- Site filter -->
				<form method="get" class="flex items-center gap-2">
					<input type="hidden" name="page" value="<?php echo esc_attr( Network_Central_Cpt::PAGE_SLUG ); ?>">
					<input type="hidden" name="nc_type" value="<?php echo esc_attr( $active_type ); ?>">
					<select name="nc_site"
						class="border border-slate-600 text-sm rounded-lg px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-cyan-500">
						<option value="0" <?php selected( 0, $filter_site ); ?>><?php esc_html_e( 'All sites', 'network-central' ); ?></option>
						<?php foreach ( $all_sites as $s ) : ?>
							<?php
							switch_to_blog( $s->blog_id );
							$s_name = get_bloginfo( 'name' );
							restore_current_blog();
							?>
							<option value="<?php echo (int) $s->blog_id; ?>" <?php selected( (int) $s->blog_id, $filter_site ); ?>>
								<?php echo esc_html( $s_name ); ?> (ID:<?php echo (int) $s->blog_id; ?>)
							</option>
						<?php endforeach; ?>
					</select>
					<button type="submit" class="px-3 py-1.5 rounded-lg bg-slate-700 border border-slate-600 text-slate-300 text-sm hover:bg-slate-600 transition">
						<?php esc_html_e( 'Filter', 'network-central' ); ?>
					</button>
					<span class="text-slate-600 text-xs ml-2">
						<?php printf( esc_html__( '%d total', 'network-central' ), (int) $total ); ?>
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
								<th class="px-4 py-3 text-left font-medium"><?php esc_html_e( 'Title', 'network-central' ); ?></th>
								<th class="px-4 py-3 text-left font-medium"><?php esc_html_e( 'Author', 'network-central' ); ?></th>
								<th class="px-4 py-3 text-left font-medium"><?php esc_html_e( 'Status', 'network-central' ); ?></th>
								<th class="px-4 py-3 text-left font-medium"><?php esc_html_e( 'Date', 'network-central' ); ?></th>
								<th class="px-4 py-3 text-left font-medium"><?php esc_html_e( 'Site', 'network-central' ); ?></th>
								<th class="px-4 py-3"></th>
							</tr>
						</thead>
						<tbody class="divide-y divide-slate-700/40">
							<?php foreach ( $rows as $r ) : ?>
								<tr class="hover:bg-slate-800/40 transition">
									<td class="px-4 py-3 text-slate-100 font-medium"><?php echo esc_html( $r['title'] ?: __( '(no title)', 'network-central' ) ); ?></td>
									<td class="px-4 py-3 text-slate-400"><?php echo esc_html( $r['author'] ); ?></td>
									<td class="px-4 py-3"><?php echo self::post_status_badge( $r['status'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
									<td class="px-4 py-3 text-slate-400 text-xs"><?php echo esc_html( $r['date'] ? date_i18n( get_option( 'date_format' ), strtotime( $r['date'] ) ) : '—' ); ?></td>
									<td class="px-4 py-3 text-slate-500 text-xs"><?php echo esc_html( $r['site_name'] ); ?></td>
									<td class="px-4 py-3">
										<a href="<?php echo esc_url( $r['edit_url'] ); ?>" class="text-cyan-400 hover:text-cyan-300 text-xs transition underline underline-offset-2">
											<?php esc_html_e( 'Edit', 'network-central' ); ?>
										</a>
									</td>
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
									<a href="<?php echo esc_url( add_query_arg( array( 'nc_type' => $active_type, 'nc_site' => $filter_site, 'paged' => $paged - 1 ), $base_url ) ); ?>"
										class="px-3 py-1 rounded border border-slate-600 text-slate-300 hover:bg-slate-800 text-xs transition">
										← <?php esc_html_e( 'Prev', 'network-central' ); ?>
									</a>
								<?php endif; ?>
								<?php if ( $paged < $total_pages ) : ?>
									<a href="<?php echo esc_url( add_query_arg( array( 'nc_type' => $active_type, 'nc_site' => $filter_site, 'paged' => $paged + 1 ), $base_url ) ); ?>"
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

	// ── Helpers ───────────────────────────────────────────────────────────

	private static function post_status_badge( $status ) {
		$map = array(
			'publish' => array( 'text-emerald-400', __( 'Published', 'network-central' ) ),
			'draft'   => array( 'text-slate-400',   __( 'Draft', 'network-central' ) ),
			'pending' => array( 'text-amber-400',   __( 'Pending', 'network-central' ) ),
			'private' => array( 'text-purple-400',  __( 'Private', 'network-central' ) ),
		);
		$s = isset( $map[ $status ] ) ? $map[ $status ] : array( 'text-slate-500', ucfirst( $status ) );
		return '<span class="' . esc_attr( $s[0] ) . ' text-xs">' . esc_html( $s[1] ) . '</span>';
	}
}

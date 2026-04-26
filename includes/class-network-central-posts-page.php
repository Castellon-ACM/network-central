<?php
/**
 * Blog Network Manager page renderer.
 *
 * @package NetworkCentral
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Network_Central_Posts_Page
 */
class Network_Central_Posts_Page {

	/**
	 * Render the Blog Network Manager page.
	 *
	 * @return void
	 */
	public static function render() {
		$all_sites   = Network_Central_Posts::get_all_sites();
		$active_tab  = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'posts'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$filter_site = isset( $_GET['nc_site'] ) ? (int) $_GET['nc_site'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$paged       = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$per_page    = 25;
		$base_url    = network_admin_url( 'admin.php?page=' . Network_Central_Posts::PAGE_SLUG );

		$tabs = array(
			'posts'    => __( 'Posts', 'network-central' ),
			'pages'    => __( 'Pages', 'network-central' ),
			'comments' => __( 'Comments', 'network-central' ),
		);

		switch ( $active_tab ) {
			case 'pages':
				$rows  = Network_Central_Posts::get_pages( $filter_site, $per_page, $paged );
				$total = Network_Central_Posts::count_pages( $filter_site );
				break;
			case 'comments':
				$rows  = Network_Central_Posts::get_comments( $filter_site, $per_page, $paged );
				$total = Network_Central_Posts::count_comments( $filter_site );
				break;
			default:
				$active_tab = 'posts';
				$rows       = Network_Central_Posts::get_posts( $filter_site, $per_page, $paged );
				$total      = Network_Central_Posts::count_posts( $filter_site );
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
						<?php esc_html_e( 'Blog Network Manager', 'network-central' ); ?>
					</h1>
					<p class="text-slate-500 text-sm mt-1"><?php esc_html_e( 'Manage posts, pages and comments across all network sites', 'network-central' ); ?></p>
				</div>
				<a href="<?php echo esc_url( network_admin_url( 'admin.php?page=' . NETWORK_CENTRAL_PAGE_SLUG ) ); ?>"
					class="text-slate-500 hover:text-slate-300 text-sm transition">
					← <?php esc_html_e( 'Network Central', 'network-central' ); ?>
				</a>
			</header>

			<!-- Tabs + filter bar -->
			<div class="flex items-center justify-between mb-6 flex-wrap gap-4">

				<!-- Tabs -->
				<nav class="flex gap-1 bg-slate-900/60 border border-slate-700/60 rounded-lg p-1">
					<?php foreach ( $tabs as $slug => $label ) : ?>
						<?php
						$tab_url   = add_query_arg( array( 'tab' => $slug, 'nc_site' => $filter_site, 'paged' => 1 ), $base_url );
						$tab_class = $active_tab === $slug
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
					<input type="hidden" name="page" value="<?php echo esc_attr( Network_Central_Posts::PAGE_SLUG ); ?>">
					<input type="hidden" name="tab" value="<?php echo esc_attr( $active_tab ); ?>">
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

		</div>
		</div>
		<?php
	}

	// ── Table heads ───────────────────────────────────────────────────────

	private static function render_thead( $tab ) {
		$cols = array(
			'posts'    => array( 'Title', 'Author', 'Status', 'Date', 'Site', '' ),
			'pages'    => array( 'Title', 'Author', 'Status', 'Date', 'Site', '' ),
			'comments' => array( 'Author', 'Email', 'Content', 'Status', 'Post', 'Date', 'Site', '' ),
		);
		$headers = isset( $cols[ $tab ] ) ? $cols[ $tab ] : array();
		foreach ( $headers as $h ) {
			echo '<th class="px-4 py-3 text-left font-medium">' . esc_html( $h ? __( $h, 'network-central' ) : '' ) . '</th>'; // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
		}
	}

	// ── Table rows ────────────────────────────────────────────────────────

	private static function render_row( $tab, $r ) {
		switch ( $tab ) {
			case 'posts':
			case 'pages':
				self::cell( $r['title'] ?: __( '(no title)', 'network-central' ), 'text-slate-100 font-medium' );
				self::cell( $r['author'], 'text-slate-400' );
				echo '<td class="px-4 py-3">' . self::post_status_badge( $r['status'] ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				self::cell( $r['date'] ? date_i18n( get_option( 'date_format' ), strtotime( $r['date'] ) ) : '—', 'text-slate-400 text-xs' );
				self::cell( $r['site_name'], 'text-slate-500 text-xs' );
				self::edit_cell( $r['edit_url'] );
				break;

			case 'comments':
				self::cell( $r['author'], 'text-slate-100 font-medium' );
				self::cell( $r['email'] ?: '—', 'text-slate-400 text-xs' );
				self::cell( $r['content'], 'text-slate-400 max-w-xs truncate' );
				echo '<td class="px-4 py-3">' . self::comment_status_badge( $r['status'] ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				self::cell( $r['post_title'], 'text-slate-400 text-xs' );
				self::cell( $r['date'] ? date_i18n( get_option( 'date_format' ), strtotime( $r['date'] ) ) : '—', 'text-slate-400 text-xs' );
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

	private static function comment_status_badge( $status ) {
		$map = array(
			'1'    => 'bg-emerald-500/15 border-emerald-500/40 text-emerald-300',
			'0'    => 'bg-amber-500/15 border-amber-500/40 text-amber-300',
			'spam' => 'bg-red-500/15 border-red-500/40 text-red-300',
		);
		$labels = array(
			'1'    => __( 'Approved', 'network-central' ),
			'0'    => __( 'Pending', 'network-central' ),
			'spam' => __( 'Spam', 'network-central' ),
		);
		$cls   = isset( $map[ $status ] ) ? $map[ $status ] : 'bg-slate-700/60 border-slate-600 text-slate-400';
		$label = isset( $labels[ $status ] ) ? $labels[ $status ] : ucfirst( $status );
		return '<span class="px-2 py-0.5 rounded-full border text-xs ' . esc_attr( $cls ) . '">' . esc_html( $label ) . '</span>';
	}
}

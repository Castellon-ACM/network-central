<?php
/**
 * Blog network management — data queries for posts, pages and comments.
 *
 * @package NetworkCentral
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Network_Central_Posts
 */
class Network_Central_Posts {

	const OPTION_KEY   = 'network_central_posts_enabled';
	const NONCE_ACTION = 'network_central_posts_toggle';
	const PAGE_SLUG    = 'network-central-posts';

	// ── Option ────────────────────────────────────────────────────────────

	public static function is_enabled() {
		return (bool) get_site_option( self::OPTION_KEY, false );
	}

	public static function set_enabled( $enabled ) {
		update_site_option( self::OPTION_KEY, (bool) $enabled );
	}

	// ── Site list ─────────────────────────────────────────────────────────

	public static function get_all_sites() {
		return get_sites( array( 'number' => 200, 'deleted' => 0, 'archived' => 0, 'spam' => 0 ) );
	}

	// ── Posts ─────────────────────────────────────────────────────────────

	public static function count_posts( $filter_site_id = 0 ) {
		global $wpdb;
		$total = 0;
		foreach ( self::_sites( $filter_site_id ) as $site ) {
			switch_to_blog( $site->blog_id );
			$p      = $wpdb->prefix;
			$total += (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$p}posts WHERE post_type='post' AND post_status IN('publish','draft','pending','private')" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			restore_current_blog();
		}
		return $total;
	}

	public static function get_posts( $filter_site_id = 0, $per_page = 25, $paged = 1 ) {
		global $wpdb;
		$results = array();
		$offset  = ( max( 1, $paged ) - 1 ) * $per_page;

		foreach ( self::_sites( $filter_site_id ) as $site ) {
			switch_to_blog( $site->blog_id );
			$p         = $wpdb->prefix;
			$u         = $wpdb->base_prefix . 'users';
			$site_name = get_bloginfo( 'name' );
			$rows      = $wpdb->get_results( // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->prepare(
					"SELECT p.ID, p.post_title, p.post_status, p.post_date, u.display_name AS author_name
					 FROM {$p}posts p
					 LEFT JOIN {$u} u ON u.ID = p.post_author
					 WHERE p.post_type='post'
					   AND p.post_status IN('publish','draft','pending','private')
					 ORDER BY p.post_date DESC
					 LIMIT %d OFFSET %d",
					$per_page, $offset
				)
			);
			foreach ( $rows as $r ) {
				$results[] = array(
					'site_id'   => (int) $site->blog_id,
					'site_name' => $site_name,
					'id'        => (int) $r->ID,
					'title'     => $r->post_title,
					'status'    => $r->post_status,
					'date'      => $r->post_date,
					'author'    => $r->author_name ?: '—',
					'edit_url'  => get_admin_url( $site->blog_id, 'post.php?post=' . $r->ID . '&action=edit' ),
				);
			}
			restore_current_blog();
		}
		return $results;
	}

	// ── Pages ─────────────────────────────────────────────────────────────

	public static function count_pages( $filter_site_id = 0 ) {
		global $wpdb;
		$total = 0;
		foreach ( self::_sites( $filter_site_id ) as $site ) {
			switch_to_blog( $site->blog_id );
			$p      = $wpdb->prefix;
			$total += (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$p}posts WHERE post_type='page' AND post_status IN('publish','draft','pending','private')" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			restore_current_blog();
		}
		return $total;
	}

	public static function get_pages( $filter_site_id = 0, $per_page = 25, $paged = 1 ) {
		global $wpdb;
		$results = array();
		$offset  = ( max( 1, $paged ) - 1 ) * $per_page;

		foreach ( self::_sites( $filter_site_id ) as $site ) {
			switch_to_blog( $site->blog_id );
			$p         = $wpdb->prefix;
			$u         = $wpdb->base_prefix . 'users';
			$site_name = get_bloginfo( 'name' );
			$rows      = $wpdb->get_results( // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->prepare(
					"SELECT p.ID, p.post_title, p.post_status, p.post_date, u.display_name AS author_name
					 FROM {$p}posts p
					 LEFT JOIN {$u} u ON u.ID = p.post_author
					 WHERE p.post_type='page'
					   AND p.post_status IN('publish','draft','pending','private')
					 ORDER BY p.post_date DESC
					 LIMIT %d OFFSET %d",
					$per_page, $offset
				)
			);
			foreach ( $rows as $r ) {
				$results[] = array(
					'site_id'   => (int) $site->blog_id,
					'site_name' => $site_name,
					'id'        => (int) $r->ID,
					'title'     => $r->post_title,
					'status'    => $r->post_status,
					'date'      => $r->post_date,
					'author'    => $r->author_name ?: '—',
					'edit_url'  => get_admin_url( $site->blog_id, 'post.php?post=' . $r->ID . '&action=edit' ),
				);
			}
			restore_current_blog();
		}
		return $results;
	}

	// ── Comments ──────────────────────────────────────────────────────────

	public static function count_comments( $filter_site_id = 0 ) {
		global $wpdb;
		$total = 0;
		foreach ( self::_sites( $filter_site_id ) as $site ) {
			switch_to_blog( $site->blog_id );
			$p      = $wpdb->prefix;
			$total += (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$p}comments WHERE comment_approved IN('0','1','spam')" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			restore_current_blog();
		}
		return $total;
	}

	public static function get_comments( $filter_site_id = 0, $per_page = 25, $paged = 1 ) {
		global $wpdb;
		$results = array();
		$offset  = ( max( 1, $paged ) - 1 ) * $per_page;

		foreach ( self::_sites( $filter_site_id ) as $site ) {
			switch_to_blog( $site->blog_id );
			$p         = $wpdb->prefix;
			$site_name = get_bloginfo( 'name' );
			$rows      = $wpdb->get_results( // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->prepare(
					"SELECT c.comment_ID, c.comment_author, c.comment_author_email,
					        c.comment_content, c.comment_approved, c.comment_date,
					        p.post_title, p.ID AS post_id
					 FROM {$p}comments c
					 LEFT JOIN {$p}posts p ON p.ID = c.comment_post_ID
					 WHERE c.comment_approved IN('0','1','spam')
					 ORDER BY c.comment_date DESC
					 LIMIT %d OFFSET %d",
					$per_page, $offset
				)
			);
			foreach ( $rows as $r ) {
				$results[] = array(
					'site_id'    => (int) $site->blog_id,
					'site_name'  => $site_name,
					'id'         => (int) $r->comment_ID,
					'author'     => $r->comment_author ?: '—',
					'email'      => $r->comment_author_email,
					'content'    => wp_trim_words( $r->comment_content, 12, '…' ),
					'status'     => $r->comment_approved,
					'date'       => $r->comment_date,
					'post_title' => $r->post_title ?: '—',
					'edit_url'   => get_admin_url( $site->blog_id, 'comment.php?action=editcomment&c=' . $r->comment_ID ),
				);
			}
			restore_current_blog();
		}
		return $results;
	}

	// ── Helpers ───────────────────────────────────────────────────────────

	private static function _sites( $filter_site_id ) {
		$sites = self::get_all_sites();
		if ( ! $filter_site_id ) {
			return $sites;
		}
		return array_filter(
			$sites,
			function( $s ) use ( $filter_site_id ) {
				return (int) $s->blog_id === (int) $filter_site_id;
			}
		);
	}
}

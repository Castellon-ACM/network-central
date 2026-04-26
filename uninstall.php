<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Does not remove wp-config.php constants or .htaccess rules —
 * those are the site's infrastructure and must be managed manually.
 *
 * @package NetworkCentral
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

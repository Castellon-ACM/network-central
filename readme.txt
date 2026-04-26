=== Network Central ===
Contributors: Castellon-ACM
Tags: multisite, network, wp-config, htaccess, network setup
Requires at least: 5.6
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Enable or disable WordPress Multisite with a single toggle from the admin dashboard.

== Description ==

**Network Central** converts a standard WordPress installation into a Multisite network (subdirectory mode) with a single toggle — no manual file editing required.

= What happens when you enable =

* Writes the 7 required constants to `wp-config.php` (`WP_ALLOW_MULTISITE`, `MULTISITE`, `SUBDOMAIN_INSTALL`, `DOMAIN_CURRENT_SITE`, `PATH_CURRENT_SITE`, `SITE_ID_CURRENT_SITE`, `BLOG_ID_CURRENT_SITE`)
* Replaces the WordPress single-site rewrite block in `.htaccess` with the Multisite subdirectory rules
* Creates the network tables in the database (`wp_site`, `wp_blogs`, etc.) via `install_network()` and `populate_network()` — no manual visit to Tools → Network Setup needed
* Redirects automatically to the Network Admin (`wp-admin/network.php`)

= What happens when you disable =

* Removes all Multisite constants from `wp-config.php`
* Restores single-site rewrite rules in `.htaccess`

= System status panel =

The page shows in real time whether Multisite is active, whether `wp-config.php` and `.htaccess` are writable, and the current PHP and WordPress versions.

= Requirements =

* PHP 7.4 or higher
* `wp-config.php` with write permissions
* Apache server with `.htaccess` support, or ability to manually edit Nginx rewrite rules

== Installation ==

1. Upload the `network-central` folder to `/wp-content/plugins/`.
2. Activate the plugin from **Plugins → Installed Plugins**.
3. Go to **Network Central** in the admin sidebar.
4. Toggle the switch and click **Save**.

== Frequently Asked Questions ==

= Does this support subdomain installs? =

No. Network Central configures **subdirectory** mode only (e.g. `mysite.com/store/`). For subdomain installs, edit `wp-config.php` manually and set `SUBDOMAIN_INSTALL` to `true`.

= What if wp-config.php is not writable? =

The toggle is disabled and a notice is shown. Fix the file permissions (`chmod 644` or `chmod 664`) before using the plugin.

= Will disabling Multisite delete subsite data? =

No. Subsite tables (`wp_2_posts`, `wp_2_options`, etc.) remain in the database. The plugin only removes the configuration constants from `wp-config.php` and restores the `.htaccess` rewrite rules. To remove network tables, do it manually via phpMyAdmin.

= Is it compatible with Nginx? =

The plugin writes `.htaccess`, which Nginx ignores. On Nginx, enable the toggle to let the plugin update `wp-config.php` and create the network tables, then add the Multisite rewrite rules to your Nginx config manually.

= What if .htaccess is not writable? =

The plugin writes `wp-config.php` and installs the network tables regardless. The status panel flags that `.htaccess` is not writable so you can update it manually.

== Screenshots ==

1. Main page with the Multisite toggle and system status panel.

== Changelog ==

= 1.0.1 =
* Renamed all internal files from class-nc-*.php to class-network-central-*.php.
* Renamed all classes, constants, and functions from the nc_ / NC_ prefix to network_central_ / Network_Central_ to match the plugin slug convention.
* All UI strings switched to English only.
* Added uninstall.php — safe no-op since wp-config.php and .htaccess are user infrastructure.
* Added languages/network-central.pot with all translatable strings.
* Added composer.json for development dependency management and test runner config.
* Added Author URI, Domain Path, and network_central_plugin_init() for proper text domain loading.

= 1.0.0 =
* Initial release.
* Single toggle to enable or disable WordPress Multisite.
* Automatic write of constants to wp-config.php.
* Automatic update of .htaccess rewrite rules.
* Network tables created via install_network() and populate_network().
* System status panel (Multisite, wp-config, htaccess, PHP, WordPress).
* Dark UI with Tailwind CSS, consistent style with Settinator.

== Upgrade Notice ==

= 1.0.1 =
No breaking changes. Renames internal identifiers to match plugin slug convention. Safe to update.

= 1.0.0 =
First stable release.

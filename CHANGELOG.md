# Changelog

All notable changes to **Network Central** are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.3] – 2026-04-26

### Added
- Translations for Spanish (`es_ES`), French (`fr_FR`), Italian (`it_IT`), and Portuguese (`pt_PT`) — 30 strings each, compiled to `.mo` binaries.
- `languages/compile-mo.php` — PHP script to recompile `.po` files to `.mo` without external tools.
- `fonts/JetBrainsMono-Regular.woff2`, `fonts/JetBrainsMono-Medium.woff2`, `fonts/JetBrainsMono-SemiBold.woff2` — JetBrains Mono font bundled locally.
- `assets/js/tailwind.min.js` — Tailwind CSS Play CDN bundled locally.

### Changed
- JetBrains Mono font is now served from the plugin's `fonts/` directory instead of Google Fonts — no external font request.
- Tailwind CSS is now loaded from the plugin's `assets/js/tailwind.min.js` instead of `cdn.tailwindcss.com` — no external script request.

## [1.0.2] – 2026-04-26

### Added
- `nc_ok=enabled` success notice on the plugin page after enabling Multisite.
- `network_central_admin_url()` helper that returns `network_admin_url()` when in Multisite context and `admin_url()` otherwise.

### Fixed
- Constants were appended after `require_once ABSPATH . 'wp-settings.php'` on installations whose `wp-config.php` uses a non-standard stop-editing comment. `enable_multisite_full()` now falls back to inserting the constants block immediately before the `require_once` line, so WordPress always boots with Multisite constants defined.
- After enabling Multisite the plugin now redirects to its own page (`?nc_ok=enabled`) instead of `admin_url('network.php')`. The previous target caused WordPress to intercept the request and redirect to `network/setup.php`, blocking the user.
- Plugin menu is now registered via `network_admin_menu` (and capability `manage_network`) when Multisite is active, so it appears in the Network Admin instead of individual site admins.
- Tailwind CSS and JetBrains Mono font are now output directly inside `render()` instead of going through `wp_enqueue_script` / `wp_enqueue_style`. The enqueue hooks fired with the wrong screen context in the Network Admin, so assets were silently skipped and the page rendered unstyled.
- `run_network_install()` now always calls `install_network()` (idempotent via `dbDelta`) and calls `populate_network()` only when `network_domain_check()` returns false, then unconditionally calls `grant_super_admin()` for the current user. The previous logic could skip `populate_network()` on a re-run and leave the user without super-admin status, showing "Sorry, you are not allowed to access this page" on `network/setup.php`.
- `.htaccess` rewrite rule was missing `.*` before `\.php` — the pattern `(\.php)$` only matched files literally named `.php`, so `index.php`, `wp-login.php`, and all PHP files inside subdirectory-style multisite URLs were not rewritten. This caused 403 on every network admin page after enabling Multisite.
- Rewrite block replacement in `add_multisite_rules()` now uses `trim()` to avoid duplicate blank lines when replacing the WordPress single-site block.
- `restore_single_site_rules()` now uses `trim()` consistently on the single-site block when replacing the Multisite block.
- `enable_multisite_full()` now validates PHP syntax with `validate_syntax()` before writing `wp-config.php`, preventing a corrupt file from being saved.
- `enable_multisite_full()` and `disable_multisite_full()` now use `[\'" ]` (with space) in the removal regex to handle non-standard `define()` formatting in `wp-config.php`.
- `run_network_install()` now defines each constant with individual `defined()` guards instead of a loop.
- `run_network_install()` promoted from `private` to `public`.
- `enable()` method now updates `.htaccess` before checking `$ok` and returning early.

## [1.0.1] – 2026-04-26

### Added
- `uninstall.php` — safe no-op on uninstall; wp-config.php constants and .htaccess rules are user infrastructure and are not removed automatically.
- `languages/network-central.pot` — POT template with all translatable strings for community translators.
- `composer.json` — development dependency management (`phpunit/phpunit`, `brain/monkey`) and test runner scripts.
- `Author URI` and `Domain Path` headers to the main plugin file.
- `network_central_plugin_init()` — loads the text domain on `plugins_loaded` so translations work correctly.

### Changed
- Renamed all include files from `class-nc-*.php` to `class-network-central-*.php` to match the plugin slug convention (mirrors t-backup's `class-tbackup.php` pattern).
- Renamed all PHP classes from `NC_*` to `Network_Central_*` (`NC_Wpconfig` → `Network_Central_Wpconfig`, etc.).
- Renamed all constants from `NC_*` to `NETWORK_CENTRAL_*` (`NC_VERSION` → `NETWORK_CENTRAL_VERSION`, etc.).
- Renamed all functions from `nc_*` to `network_central_*` (`nc_add_menu_page` → `network_central_add_menu_page`, etc.).
- Renamed nonce field from `nc_multisite_nonce` to `network_central_nonce` and POST key from `nc_multisite` to `network_central_multisite`.
- All UI strings are now English only.

## [1.0.0] – 2026-04-26

### Added
- Single toggle in the admin dashboard to enable or disable WordPress Multisite (subdirectory install).
- `NC_Wpconfig` — writes and removes the 7 Multisite constants in `wp-config.php` (`WP_ALLOW_MULTISITE`, `MULTISITE`, `SUBDOMAIN_INSTALL`, `DOMAIN_CURRENT_SITE`, `PATH_CURRENT_SITE`, `SITE_ID_CURRENT_SITE`, `BLOG_ID_CURRENT_SITE`). OPcache is invalidated after each write.
- `NC_Htaccess` — replaces the WordPress single-site rewrite block with Multisite subdirectory rules on enable, and restores single-site rules on disable.
- `NC_Multisite` — orchestrator: calls `install_network()` and `populate_network()` in the same request to create network tables (`wp_site`, `wp_blogs`, etc.) without requiring a manual visit to Tools → Network Setup.
- `NC_Page` — admin page with accessible toggle, system status panel (Multisite active, wp-config writable, .htaccess writable, PHP version, WordPress version), and error/success notices.
- Top-level admin menu with `dashicons-networking` icon at position 79.
- Nonce verification and `manage_options` capability check on all form submissions.
- Compatible with PHP 7.4+ (no `match`, no `enum`, no PHP 8-only syntax).
- Dark UI with Tailwind CSS CDN and JetBrains Mono font, consistent with the Settinator plugin style.

[Unreleased]: https://github.com/Castellon-ACM/network-central/compare/v1.0.2...HEAD
[1.0.2]: https://github.com/Castellon-ACM/network-central/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/Castellon-ACM/network-central/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/Castellon-ACM/network-central/releases/tag/v1.0.0

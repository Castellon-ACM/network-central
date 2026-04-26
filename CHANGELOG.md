# Changelog

All notable changes to **Network Central** are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

[Unreleased]: https://github.com/Castellon-ACM/network-central/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/Castellon-ACM/network-central/releases/tag/v1.0.0

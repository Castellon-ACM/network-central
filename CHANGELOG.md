# Changelog

All notable changes to **Network Central** are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] – 2026-04-26

### Added
- Toggle único en el escritorio de administración para activar o desactivar WordPress Multisite (subdirectorios).
- `NC_Wpconfig` — escribe y elimina las 7 constantes Multisite en `wp-config.php` (`WP_ALLOW_MULTISITE`, `MULTISITE`, `SUBDOMAIN_INSTALL`, `DOMAIN_CURRENT_SITE`, `PATH_CURRENT_SITE`, `SITE_ID_CURRENT_SITE`, `BLOG_ID_CURRENT_SITE`). Invalida OPcache tras cada escritura.
- `NC_Htaccess` — reemplaza las reglas WordPress single-site por las reglas Multisite en `.htaccess` al activar, y las restaura al desactivar.
- `NC_Multisite` — orquestador: llama a `install_network()` y `populate_network()` en la misma petición para crear las tablas de red (`wp_site`, `wp_blogs`, etc.) sin necesidad de visitar manualmente Tools → Network Setup.
- `NC_Page` — página de administración con toggle accesible, panel de estado del sistema (Multisite activo, wp-config escribible, .htaccess escribible, versión PHP, versión WP) y avisos de error/éxito.
- Menú de administración propio con icono `dashicons-networking`, posición 79.
- Nonce verification y `manage_options` capability check en todos los formularios.
- Compatible con PHP 7.4+ (sin uso de `match`, sin `enum`, sin sintaxis de PHP 8+).
- UI dark con Tailwind CSS CDN y JetBrains Mono, estilo coherente con el plugin Settinator.

[Unreleased]: https://github.com/Castellon-ACM/network-central/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/Castellon-ACM/network-central/releases/tag/v1.0.0
